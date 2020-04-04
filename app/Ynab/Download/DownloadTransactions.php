<?php
declare(strict_types=1);
/**
 * DownloadTransactions.php
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

namespace App\Ynab\Download;

use App\Services\Configuration\Configuration;
use App\Services\Sync\JobStatus\ProgressInformation;
use App\Ynab\Request\GetTransactionsRequest;
use App\Ynab\Response\GetTransactionsResponse;
use Log;
use Storage;

/**
 * Class DownloadTransactions
 */
class DownloadTransactions
{
    use ProgressInformation;

    /** @var Configuration */
    private $configuration;
    /** @var string */
    private $downloadIdentifier;

    /**
     * PaymentList constructor.
     *
     * @param Configuration $configuration
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     *
     */
    public function getTransactions(): iterable
    {
        // for each budget, for each account, start downloading.
        // since_date = date_not_before
        $uri       = (string) config('ynab.api_uri');
        $token     = (string) config('ynab.api_code');
        $sinceDate = '' === $this->configuration->getDateNotBefore() ? null : $this->configuration->getDateNotBefore();
        $return    = [];
        Log::debug('Now in getTransaction()');
        foreach ($this->configuration->getAccounts() as $budgetId => $list) {
            Log::debug(sprintf('Now downloading from budget %s', $budgetId));
            foreach ($list as $accountId => $import) {
                if (false !== $import) {
                    Log::debug(sprintf('Going to download from account %s', $accountId));
                    $request = new GetTransactionsRequest($uri, $token, $budgetId, $accountId, $sinceDate);
                    /** @var GetTransactionsResponse $result */
                    $result = $request->get();
                    $array  = $result->toArray();
                    Log::debug(sprintf('Found %d transaction(s) in account %s', count($array), $accountId));
                    $return[] = $array;
                }
            }
        }
        $transactions = array_merge(...$return);
        Log::debug(sprintf('Merged into %d total transactions.', count($transactions)));

        $this->storeDownload($transactions);

        return $transactions;
    }

    /**
     * @param string $downloadIdentifier
     */
    public function setDownloadIdentifier(string $downloadIdentifier): void
    {
        $this->downloadIdentifier = $downloadIdentifier;
    }
    /**
     * @param array $data
     */
    private function storeDownload(array $data): void
    {
        $disk = Storage::disk('downloads');
        $disk->put($this->downloadIdentifier, json_encode($data, JSON_THROW_ON_ERROR, 512));
    }

}