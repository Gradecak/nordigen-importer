<?php
/*
 * FilterTransactions.php
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


namespace App\Services\Sync;

use App\Services\Sync\JobStatus\ProgressInformation;
use Log;

/**
 * Class FilterTransactions
 * @deprecated
 */
class FilterTransactions
{
    use ProgressInformation;

    /**
     * FilterTransactions constructor.
     */
    public function __construct()
    {
        die('do not use6');
    }

    /**
     * @param array $transactions
     *
     * @return array
     */
    public function filter(array $transactions): array
    {
        $start  = count($transactions);
        $return = [];
        /** @var array $transaction */
        foreach ($transactions as $transaction) {

            unset($transaction['transactions'][0]['datetime']);

            if (0 === (int)($transaction['transactions'][0]['category_id'] ?? 0)) {
                //Log::debug('IS NULL');
                unset($transaction['transactions'][0]['category_id']);
            }
            $return[] = $transaction;
            // Log::debug('Filtered ', $transaction);
        }
        $end = count($return);
        $this->addMessage(0, sprintf('Filtered down from %d (possibly duplicate) entries to %d unique transactions.', $start, $end));

        return $return;
    }

}
