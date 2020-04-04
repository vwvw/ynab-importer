<?php

declare(strict_types=1);

namespace App\Http\Controllers\Import;

use App\Http\Controllers\Controller;
use App\Services\Configuration\Configuration;
use App\Services\Session\Constants;
use GrumpyDictator\FFIIIApiSupport\Exceptions\ApiHttpException;
use GrumpyDictator\FFIIIApiSupport\Request\GetAccountsRequest;
use GrumpyDictator\FFIIIApiSupport\Response\GetAccountsResponse;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Class MappingController.
 */
class MappingController extends Controller
{
    /**
     * MappingController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        app('view')->share('pageTitle', 'Map your YNAB data to Firefly III');
    }

    /**
     * @throws ApiHttpException
     */
    public function index()
    {
        $mainTitle = 'Map data';
        $subTitle  = 'Link YNAB information to Firefly III data.';

        $configuration = Configuration::fromArray([]);
        if (session()->has(Constants::CONFIGURATION)) {
            $configuration = Configuration::fromArray(session()->get(Constants::CONFIGURATION));
        }
        // if config says to skip it, skip it:
        if (null !== $configuration && false === $configuration->isDoMapping()) {
            // skipForm
            return redirect()->route('import.sync.index');
        }

        $mapping = $configuration->getMapping();

        // parse all opposing accounts from the download
        $ynabAccounts = $this->getYnabAccounts();

        // get accounts from Firefly III
        $ff3Accounts = $this->getFireflyIIIAccounts();

        return view('import.mapping.index', compact('mainTitle', 'subTitle', 'configuration', 'ynabAccounts', 'ff3Accounts', 'mapping'));
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse
     *
     * @psalm-return RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function postIndex(Request $request)
    {
        // post mapping is not particularly complex.
        $result       = $request->all();
        $mapping      = $result['mapping'] ?? [];
        $accountTypes = $result['account_type'] ?? [];

        $configuration = Configuration::fromArray([]);
        if (session()->has(Constants::CONFIGURATION)) {
            $configuration = Configuration::fromArray(session()->get(Constants::CONFIGURATION));
        }
        // if config says to skip it, skip it:
        if (null !== $configuration && false === $configuration->isDoMapping()) {
            // skipForm
            return redirect()->route('import.sync.index');
        }
        // save mapping in config.
        $configuration->setMapping($mapping);
        $configuration->setAccountTypes($accountTypes);

        // save mapping in config, save config.
        session()->put(Constants::CONFIGURATION, $configuration->toArray());

        // no need to save this step in config.

        return redirect(route('import.sync.index'));
    }

    /**
     * @throws ApiHttpException
     * @return array
     */
    private function getFireflyIIIAccounts(): array
    {
        $token   = (string) config('ynab.access_token');
        $uri     = (string) config('ynab.uri');
        $request = new GetAccountsRequest($uri, $token);
        /** @var GetAccountsResponse $result */
        $result = $request->get();
        $return = [];
        foreach ($result as $entry) {
            $type = $entry->type;
            if ('reconciliation' === $type || 'initial-balance' === $type) {
                continue;
            }
            $id                 = (int) $entry->id;
            $return[$type][$id] = $entry->name;
            if ('' !== (string) $entry->iban) {
                $return[$type][$id] = sprintf('%s (%s)', $entry->name, $entry->iban);
            }
        }
        foreach ($return as $type => $entries) {
            asort($return[$type]);
        }

        return $return;
    }

    /**
     * @throws FileNotFoundException
     * @return array
     */
    private function getYnabAccounts(): array
    {
        $downloadIdentifier = session()->get(Constants::DOWNLOAD_JOB_IDENTIFIER);
        $disk               = Storage::disk('downloads');
        $json               = $disk->get($downloadIdentifier);
        $array              = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $opposing           = [];

        /** @var array $transactionGroup */
        foreach ($array as $transactionGroup) {
            /** @var array $transaction */
            foreach ($transactionGroup['transactions'] as $transaction) {
                $accountId            = $transaction['account_id'];
                $accountName          = $transaction['account_name'];
                $payeeId              = $transaction['payee_id'];
                $payeeName            = $transaction['payee_name'];
                $opposing[$accountId] = $accountName;
                $opposing[$payeeId]   = $payeeName;
            }
        }

        return $opposing;
    }
}
