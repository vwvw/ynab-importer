<?php

/**
 * ConfigurationController.php
 * Copyright (c) 2020 james@firefly-iii.org.
 *
 * This file is part of the Firefly III bunq importer
 * (https://github.com/firefly-iii/bunq-importer).
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

use App\Http\Controllers\Controller;
use App\Http\Middleware\BudgetComplete;
use App\Http\Request\BudgetPostRequest;
use App\Services\Configuration\Configuration;
use App\Services\Session\Constants;
use App\Services\Storage\StorageService;
use App\Ynab\Request\GetBudgetsRequest;
use App\Ynab\Response\GetBudgetsResponse;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Class BudgetController.
 */
class BudgetController extends Controller
{
    /**
     * StartController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        app('view')->share('pageTitle', 'Budget selection');
        $this->middleware(BudgetComplete::class)->except('download');
    }

    /**
     * TODO at this point maybe need to do something with config? Since user just uploaded?
     *
     * @throws \App\Exceptions\YnabApiHttpException
     * @return Factory|View
     */
    public function index()
    {
        app('log')->debug(sprintf('Now at %s', __METHOD__));
        $mainTitle = 'Select your budget from YNAB';
        $subTitle  = 'Select the YNAB budget';

        $uri     = (string) config('ynab.api_uri');
        $token   = (string) config('ynab.api_code');
        $request = new GetBudgetsRequest($uri, $token);
        /** @var GetBudgetsResponse $response */
        $response = $request->get();
        $budgets  = $response->budgets;


        return view('import.budgets.index', compact('mainTitle', 'subTitle','budgets'));
    }

    /**
     * @param BudgetPostRequest $request
     *
     * @return RedirectResponse
     */
    public function postIndex(BudgetPostRequest $request)
    {
        app('log')->debug(sprintf('Now at %s', __METHOD__));
        $fromRequest = $request->getAll();

        // get config from session
        $configuration = Configuration::fromArray([]);
        if (session()->has(Constants::CONFIGURATION)) {
            $configuration = Configuration::fromArray(session()->get(Constants::CONFIGURATION));
        }

        // update config
        $configuration->setBudgets($fromRequest['budgets']);
        $configuration->setSkipBudgetSelection($fromRequest['skip_budget_selection']);

        // store config in session.
        session()->put(Constants::CONFIGURATION, $configuration->toArray());

        // set budget selection done.
        session()->put(Constants::BUDGET_COMPLETE_INDICATOR, true);

        // redirect to import things?
        return redirect()->route('import.accounts.index');
    }
}

