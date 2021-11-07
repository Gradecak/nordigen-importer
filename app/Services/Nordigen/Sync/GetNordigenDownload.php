<?php
/*
 * GetNordigenDownload.php
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

namespace App\Services\Nordigen\Sync;

use App\Services\Nordigen\Model\Transaction;
use App\Services\Nordigen\Sync\JobStatus\ProgressInformation;
use JsonException;
use League\Flysystem\FileNotFoundException;
use Log;
use Storage;

/**
 * Class GetNordigenDownload
 */
class GetNordigenDownload
{
    use ProgressInformation;

    /**
     * @param string $downloadIdentifier
     *
     * @return array
     */
    public function getDownload(string $downloadIdentifier): array
    {
        $disk   = Storage::disk('downloads');
        $result = [];
        $count  = 0;
        if ($disk->exists($downloadIdentifier)) {
            try {
                $this->addMessage(0, 'Getting Nordigen download.');
                $result = json_decode($disk->get($downloadIdentifier), true, 512, JSON_THROW_ON_ERROR);
            } catch (FileNotFoundException | JsonException $e) {
                $this->addError(0, 'Could not get Nordigen download.');
                Log::error($e->getMessage());
            }
        }
        $return = [];
        foreach ($result as $key => $transactions) {
            $count        += count($transactions);
            $return[$key] = [];
            foreach ($transactions as $transaction) {
                $object                    = Transaction::fromLocalArray($transaction);
                $object->accountIdentifier = $key;
                $return[$key][]            = $object;
            }
        }
        app('log')->debug(sprintf('Got %d Nordigen account transactions.', $count));

        return $return;
    }
}
