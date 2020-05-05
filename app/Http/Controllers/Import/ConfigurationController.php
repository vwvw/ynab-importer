<?php
/**
 * ConfigurationController.php
 * Copyright (c) 2020 james@firefly-iii.org
 *
 * This file is part of the Firefly III YNAB importer
 * (https://github.com/firefly-iii/ynab-importer).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace App\Http\Controllers\Import;

use App\Exceptions\ImportException;
use App\Exceptions\YnabApiHttpException;
use App\Http\Controllers\Controller;
use App\Http\Middleware\ConfigComplete;
use App\Http\Request\ConfigurationPostRequest;
use App\Services\Configuration\Configuration;
use App\Services\Session\Constants;
use App\Services\Storage\StorageService;
use App\Ynab\Request\GetAccountsRequest as YnabAccountsRequest;
use App\Ynab\Request\GetBudgetsRequest;
use App\Ynab\Response\GetBudgetsResponse;
use GrumpyDictator\FFIIIApiSupport\Exceptions\ApiHttpException;
use GrumpyDictator\FFIIIApiSupport\Request\GetAccountsRequest;
use GrumpyDictator\FFIIIApiSupport\Response\GetAccountsResponse;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Class ConfigurationController.
 */
class ConfigurationController extends Controller
{
    /**
     * StartController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        app('view')->share('pageTitle', 'Import configuration');
        $this->middleware(ConfigComplete::class)->except('download');
    }

    /**
     * @return ResponseFactory|Response
     */
    public function download()
    {
        // do something
        $config = Configuration::fromArray(session()->get(Constants::CONFIGURATION))->toArray();
        $result = json_encode($config, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT, 512);

        $response = response($result);
        $name     = sprintf('ynab_import_config_%s.json', date('Y-m-d'));
        $response->header('Content-disposition', 'attachment; filename=' . $name)
                 ->header('Content-Type', 'application/json')
                 ->header('Content-Description', 'File Transfer')
                 ->header('Connection', 'Keep-Alive')
                 ->header('Expires', '0')
                 ->header('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
                 ->header('Pragma', 'public')
                 ->header('Content-Length', strlen($result));

        return $response;
    }

    /**
     * @throws ApiHttpException
     * @throws YnabApiHttpException
     * @return Factory|RedirectResponse|View
     */
    public function index()
    {
        app('log')->debug(sprintf('Now at %s', __METHOD__));
        $mainTitle = 'Import from YNAB';
        $subTitle  = 'Configure your YNAB import';

        // get config:
        $configuration = Configuration::fromArray([]);
        if (session()->has(Constants::CONFIGURATION)) {
            $configuration = Configuration::fromArray(session()->get(Constants::CONFIGURATION));
        }
        // if config says to skip it, skip it:
        if (null !== $configuration && true === $configuration->isSkipForm()) {
            // skipForm, go to YNAB download
            return redirect()->route('import.download.index');
        }

        $ff3Accounts  = $this->getFireflyIIIAccounts();
        $ynabAccounts = $this->getYnabAccounts($configuration);

        return view('import.configuration.index', compact('mainTitle', 'subTitle', 'ff3Accounts', 'ynabAccounts', 'configuration'));
    }

    /**
     * @param ConfigurationPostRequest $request
     *
     * @return RedirectResponse
     */
    public function postIndex(ConfigurationPostRequest $request): RedirectResponse
    {
        app('log')->debug(sprintf('Now at %s', __METHOD__));
        // store config on drive.

        $fromRequest = $request->getAll();

        // get config from session
        $configuration = Configuration::fromArray([]);
        if (session()->has(Constants::CONFIGURATION)) {
            $configuration = Configuration::fromArray(session()->get(Constants::CONFIGURATION));
        }

        // append data
        $accounts = [];
        foreach ($fromRequest['do_import'] as $budgetId => $list) {
            foreach (array_keys($list) as $accountId) {
                $accounts[$budgetId][$accountId] = false;
                if (isset($fromRequest['accounts'][$accountId])) {
                    $accounts[$budgetId][$accountId] = (int) $fromRequest['accounts'][$accountId];
                }
            }
        }
        $configuration->setAccounts($accounts);
        $configuration->setRules($fromRequest['rules']);
        $configuration->setSkipForm($fromRequest['skip_form']);

        // date etc.
        $configuration->setDateNotBefore($fromRequest['date_not_before'] ?? '');
        $configuration->setDateNotAfter($fromRequest['date_not_after'] ?? '');
        $configuration->setDateRange($fromRequest['date_range'] ?? 'all');
        $configuration->setDateRangeNumber($fromRequest['date_range_number'] ?? 30);

        // respond to date set:
        $configuration->updateDates();

        $configuration->setDoMapping($fromRequest['do_mapping']);

        // store in session, store on drive.
        session()->put(Constants::CONFIGURATION, $configuration->toArray());
        $config = StorageService::storeContent(json_encode($configuration, JSON_THROW_ON_ERROR, 512));

        session()->put(Constants::CONFIGURATION, $configuration->toArray());

        // set config as complete.
        session()->put(Constants::CONFIG_COMPLETE_INDICATOR, true);

        // redirect to import things?
        return redirect()->route('import.download.index');
    }

    /**
     * @throws YnabApiHttpException
     * @return array
     */
    private function getApiBudgets(): array
    {
        $uri     = (string) config('ynab.api_uri');
        $token   = (string) config('ynab.api_code');
        $request = new GetBudgetsRequest($uri, $token);
        /** @var GetBudgetsResponse $budgets */
        $budgets = $request->get();
        $result  = [];
        foreach ($budgets as $budget) {
            $result[$budget->id] = $budget->toArray();
        }

        return $result;
    }

    /**
     * @throws ApiHttpException
     * @return iterable
     */
    private function getFireflyIIIAccounts(): iterable
    {
        // get list of asset accounts in Firefly III
        $uri     = (string) config('ynab.uri');
        $token   = (string) config('ynab.access_token');
        $request = new GetAccountsRequest($uri, $token);
        $request->setType(GetAccountsRequest::ASSET);

        /** @var GetAccountsResponse $ff3Accounts */
        return $request->get();
    }

    /**
     * @param Configuration $configuration
     *
     * @throws YnabApiHttpException
     * @return iterable
     */
    private function getYnabAccounts(Configuration $configuration): iterable
    {

        $budgets    = $configuration->getBudgets();
        $apiBudgets = $this->getApiBudgets();
        $uri        = (string) config('ynab.api_uri');
        $token      = (string) config('ynab.api_code');
        $return     = [];
        /** @var string $budgetId */
        foreach ($budgets as $budgetId) {
            $return[$budgetId] = $return[$budgetId] ?? [
                    'id'       => $budgetId,
                    'budget'   => $apiBudgets[$budgetId] ?? [],
                    'accounts' => [],
                ];
            $request           = new YnabAccountsRequest($uri, $token, $budgetId);
            /** @var GetAccountsResponse $set */
            $set = $request->get();
            // TODO better code.
            foreach ($set as $account) {
                $return[$budgetId]['accounts'][] = $account;
            }
        }

        return $return;
    }
}
