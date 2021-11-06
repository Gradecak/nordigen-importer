<?php
/*
 * AssetAccountCollector.php
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

namespace App\Services\FireflyIII\Services;

use GrumpyDictator\FFIIIApiSupport\Model\Account;
use GrumpyDictator\FFIIIApiSupport\Request\GetAccountsRequest;

/**
 * Class AssetAccountCollector
 */
class AssetAccountCollector
{
    /**
     * @return array
     */
    public static function collectAssetAccounts(): array
    {
        // get list of asset accounts in Firefly III
        $url         = (string) config('importer.url');
        $token       = (string) config('importer.access_token');
        $accountList = new GetAccountsRequest($url, $token);

        $accountList->setVerify(config('importer.connection.verify'));
        $accountList->setTimeOut(config('importer.connection.timeout'));

        $accountList->setType(GetAccountsRequest::ASSET);
        $ff3Accounts = $accountList->get();
        $return      = [];

        /** @var Account $ff3Account */
        foreach ($ff3Accounts as $ff3Account) {
            $ff3Account->url = sprintf('%saccounts/show/%d', config('importer.url'), $ff3Account->id);
            $return[]        = $ff3Account;
        }

        return $return;
    }

}
