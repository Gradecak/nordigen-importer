<?php
/*
 * RoutineManager.php
 * Copyright (c) 2021 james@firefly-iii.org
 *
 * This file is part of the Firefly III Nordigen importer
 * (https://github.com/firefly-iii/nordigen-importer).
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

namespace App\Services\Nordigen\Download;

use App\Exceptions\ImporterErrorException;
use App\Services\Configuration\Configuration;
use JsonException;
use Storage;
use Str;
use Log;

/**
 * Class RoutineManager
 */
class RoutineManager
{
    /** @var string */
    private const DISKNAME = 'downloads';
    private array                $allErrors;
    private array                $allMessages;
    private array                $allWarnings;
    private Configuration        $configuration;
    private string               $downloadIdentifier;
    private TransactionProcessor $transactionProcessor;

    /**
     * RoutineManager constructor.
     *
     * @param string|null $downloadIdentifier
     */
    public function __construct(string $downloadIdentifier = null)
    {
        app('log')->debug('Constructed Nordigen download routine manager.');

        // get line converter
        $this->allMessages = [];
        $this->allWarnings = [];
        $this->allErrors   = [];
        $this->transactionProcessor = new TransactionProcessor;
        if (null === $downloadIdentifier) {
            app('log')->debug('Was given no download identifier, will generate one.');
            $this->generateDownloadIdentifier();
        }
        if (null !== $downloadIdentifier) {
            app('log')->debug('Was given download identifier, will use it.');
            $this->downloadIdentifier = $downloadIdentifier;
        }
    }

    /**
     *
     */
    private function generateDownloadIdentifier(): void
    {
        app('log')->debug('Going to generate download identifier.');
        $disk  = Storage::disk('jobs');
        $count = 0;
        do {
            $downloadIdentifier = Str::random();
            $count++;
            app('log')->debug(sprintf('Attempt #%d results in "%s"', $count, $downloadIdentifier));
        } while ($count < 30 && $disk->exists($downloadIdentifier));
        $this->downloadIdentifier = $downloadIdentifier;
        app('log')->info(sprintf('Download job identifier is "%s"', $downloadIdentifier));
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
     * @param Configuration $configuration
     */
    public function setConfiguration(Configuration $configuration): void
    {
        $this->configuration = $configuration;
        $this->transactionProcessor->setConfiguration($configuration);
        $this->transactionProcessor->setDownloadIdentifier($this->downloadIdentifier);
    }

    /**
     *
     * @throws ImporterErrorException
     */
    public function start(): void
    {
        Log::debug(sprintf('Now in %s', __METHOD__));

        // get transactions from Nordigen
        Log::debug('Call transaction processor download.');
        $transactions = $this->transactionProcessor->download();

        // store on drive in downloadIdentifier.
        $disk = Storage::disk(self::DISKNAME);
        try {
            $disk->put($this->downloadIdentifier, json_encode($transactions, JSON_THROW_ON_ERROR, 512));
        } catch (JsonException $e) {
            throw new ImporterErrorException($e->getMessage(), 0, $e);
        }
        Log::debug('Done with start function!');
    }
}
