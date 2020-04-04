<?php

declare(strict_types=1);

namespace App\Services\Sync;

use App\Exceptions\ImportException;
use App\Services\Configuration\Configuration;
use App\Services\Sync\JobStatus\JobStatusManager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Class RoutineManager.
 */
class RoutineManager
{
    /** @var array */
    private $allErrors;
    /** @var array */
    private $allMessages;
    /** @var array */
    private $allWarnings;
    /** @var Configuration */
    private $configuration;
    /** @var string */
    private $downloadIdentifier;
    /** @var string */
    private $syncIdentifier;
    /** @var GenerateTransactions */
    private $transactionGenerator;
    /** @var SendTransactions */
    private $transactionSender;
    /** @var ParseYnabDownload */
    private $ynabParser;

    /**
     * Collect info on the current job, hold it in memory.
     *
     * ImportRoutineManager constructor.
     *
     * @param null|string $syncIdentifier
     */
    public function __construct(?string $syncIdentifier = null)
    {
        app('log')->debug('Constructed RoutineManager for sync');

        $this->ynabParser           = new ParseYnabDownload;
        $this->transactionGenerator = new GenerateTransactions;
        $this->transactionSender    = new SendTransactions;

        // get line converter
        $this->allMessages = [];
        $this->allWarnings = [];
        $this->allErrors   = [];
        if (null === $syncIdentifier) {
            $this->generateSyncIdentifier();
        }
        if (null !== $syncIdentifier) {
            $this->syncIdentifier = $syncIdentifier;
        }
        $this->transactionGenerator->setIdentifier($this->syncIdentifier);
        $this->transactionSender->setIdentifier($this->syncIdentifier);
        JobStatusManager::startOrFindJob($this->syncIdentifier);
    }

    /**
     * @return array
     */
    public function getAllErrors(): array
    {
        return $this->allErrors;
    }

    /**
     * @return array
     */
    public function getAllMessages(): array
    {
        return $this->allMessages;
    }

    /**
     * @return array
     */
    public function getAllWarnings(): array
    {
        return $this->allWarnings;
    }

    /**
     * @return string
     */
    public function getDownloadIdentifier(): string
    {
        return $this->downloadIdentifier;
    }

    /**
     * @param string $downloadIdentifier
     */
    public function setDownloadIdentifier(string $downloadIdentifier): void
    {
        $this->downloadIdentifier = $downloadIdentifier;
        $this->ynabParser->setIdentifier($downloadIdentifier);
    }

    /**
     * @return string
     */
    public function getSyncIdentifier(): string
    {
        return $this->syncIdentifier;
    }

    /**
     * @param string $syncIdentifier
     */
    public function setSyncIdentifier(string $syncIdentifier): void
    {
        $this->syncIdentifier = $syncIdentifier;
    }

    /**
     * @param Configuration $configuration
     */
    public function setConfiguration(Configuration $configuration): void
    {
        $this->configuration = $configuration;
        $this->transactionGenerator->setConfiguration($configuration);
    }

    /**
     * Start the import.
     *
     * @throws ImportException
     */
    public function start(): void
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));

        // get JSON file from YNAB download
        app('log')->debug('Going to parse YNAB download.');
        $array = $this->ynabParser->getDownload($this->downloadIdentifier);
        app('log')->debug('Done parsing YNAB download.');

        // generate Firefly III ready transactions:
        $transactions = $this->transactionGenerator->getTransactions($array);
        app('log')->debug(sprintf('Generated %d Firefly III transactions.', count($transactions)));

        // send them to Firefly III!

        // send to Firefly III.
        app('log')->debug('Going to send them to Firefly III.');
        $sent = $this->transactionSender->send($transactions);
    }

    private function generateSyncIdentifier(): void
    {
        app('log')->debug('Going to generate sync job identifier.');
        $disk  = Storage::disk('jobs');
        $count = 0;
        do {
            $syncIdentifier = Str::random(16);
            $count++;
            app('log')->debug(sprintf('Attempt #%d results in "%s"', $count, $syncIdentifier));
        } while ($count < 30 && $disk->exists($syncIdentifier));
        $this->syncIdentifier = $syncIdentifier;
        app('log')->info(sprintf('Sync job identifier is "%s"', $syncIdentifier));
    }
}
