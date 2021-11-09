<?php
/*
 * TransactionProcessor.php
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

use App\Services\Configuration\Configuration;
use App\Services\Nordigen\Request\GetTransactionsRequest;
use App\Services\Nordigen\Response\GetTransactionsResponse;
use App\Services\Nordigen\TokenManager;
use Carbon\Carbon;
use Log;

/**
 * Class TransactionProcessor
 */
class TransactionProcessor
{
    /** @var string */
    private const DATE_TIME_FORMAT = 'Y-m-d H:i:s';
    private Configuration $configuration;
    private string        $downloadIdentifier;
    private ?Carbon       $notAfter;
    private ?Carbon       $notBefore;

    /**
     * @return array
     */
    public function download(): array
    {
        Log::debug(sprintf('Now in %s', __METHOD__));
        $this->notBefore = null;
        $this->notAfter  = null;
        if ('' !== (string) $this->configuration->getDateNotBefore()) {
            $this->notBefore = new Carbon($this->configuration->getDateNotBefore());
        }

        if ('' !== (string) $this->configuration->getDateNotAfter()) {
            $this->notAfter = new Carbon($this->configuration->getDateNotAfter());
        }


        $accounts = array_keys($this->configuration->getAccounts());
        $return   = [];
        foreach ($accounts as $key => $account) {
            Log::debug(sprintf('Going to download transactions for account #%d "%s"', $key, $account));
            $account = (string) $account;
            $accessToken = TokenManager::getAccessToken();
            $url         = config('importer.nordigen_url');
            $request     = new GetTransactionsRequest($url, $accessToken, $account);
            /** @var GetTransactionsResponse $transactions */
            $transactions     = $request->get();
            $return[$account] = $this->filterTransactions($transactions);
            Log::debug(sprintf('Done downloading transactions for account %s "%s"', $key, $account));
        }
        Log::debug('Done with download');

        return $return;
    }

    /**
     * @param GetTransactionsResponse $transactions
     */
    private function filterTransactions(GetTransactionsResponse $transactions): array
    {
        Log::debug(sprintf('Going to filter downloaded transactions. Original set length is %d', count($transactions)));
        if (null !== $this->notBefore) {
            Log::debug(sprintf('Will not grab transactions before "%s"', $this->notBefore->format('Y-m-d H:i:s')));
        }
        if (null !== $this->notAfter) {
            Log::debug(sprintf('Will not grab transactions after "%s"', $this->notAfter->format('Y-m-d H:i:s')));
        }
        $return = [];
        foreach ($transactions as $transaction) {
            $madeOn = $transaction->valueDate;

            if (null !== $this->notBefore && $madeOn->lte($this->notBefore)) {
                app('log')->info(
                    sprintf(
                        'Skip transaction because "%s" is before "%s".',
                        $madeOn->format(self::DATE_TIME_FORMAT),
                        $this->notBefore->format(self::DATE_TIME_FORMAT)
                    )
                );
                continue;
            }
            if (null !== $this->notAfter && $madeOn->gte($this->notAfter)) {
                app('log')->info(
                    sprintf(
                        'Skip transaction because "%s" is after "%s".',
                        $madeOn->format(self::DATE_TIME_FORMAT),
                        $this->notAfter->format(self::DATE_TIME_FORMAT)
                    )
                );

                continue;
            }
            app('log')->info(sprintf('Include transaction because date is "%s".', $madeOn->format(self::DATE_TIME_FORMAT),));
            $return[] = $transaction->toLocalArray();
        }
        Log::debug(sprintf('After filtering, set is %d transaction(s)', count($return)));

        return $return;
    }

    /**
     * @param Configuration $configuration
     */
    public function setConfiguration(Configuration $configuration): void
    {
        $this->configuration = $configuration;
    }

    /**
     * @param string $downloadIdentifier
     */
    public function setDownloadIdentifier(string $downloadIdentifier): void
    {
        $this->downloadIdentifier = $downloadIdentifier;
    }

}
