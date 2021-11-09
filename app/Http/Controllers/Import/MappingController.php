<?php
/*
 * MappingController.php
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


namespace App\Http\Controllers\Import;

use App\Exceptions\ImporterErrorException;
use App\Http\Controllers\Controller;
use App\Services\Configuration\Configuration;
use App\Services\Nordigen\Model\Transaction;
use App\Services\Session\Constants;
use GrumpyDictator\FFIIIApiSupport\Exceptions\ApiHttpException;
use GrumpyDictator\FFIIIApiSupport\Request\GetAccountsRequest;
use GrumpyDictator\FFIIIApiSupport\Request\GetCategoriesRequest;
use GrumpyDictator\FFIIIApiSupport\Response\GetAccountsResponse;
use GrumpyDictator\FFIIIApiSupport\Response\GetCategoriesResponse;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use JsonException;
use Log;

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
        app('view')->share('pageTitle', 'Map your Nordigen data to Firefly III');
    }

    /**
     * @throws ApiHttpException
     */
    public function index()
    {
        $mainTitle = 'Map data';
        $subTitle  = 'Link Nordigen information to Firefly III data.';

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
        $importerAccounts   = $this->getOpposingAccounts();
        //$spectreCategories = $this->getSpectreCategories();

        // get accounts from Firefly III
        $ff3Accounts   = $this->getFireflyIIIAccounts();
        $ff3Categories = $this->getFireflyIIICategories();

        // get categories:


        return view(
            'import.005-mapping.index',
            compact(
                'mainTitle', 'subTitle', 'configuration', 'ff3Categories',
                'importerAccounts', 'ff3Accounts', 'mapping'
            )
        );
    }

    /**
     * @return array
     * @throws ImporterErrorException
     */
    private function getOpposingAccounts(): array
    {
        Log::debug(sprintf('Now in %s', __METHOD__));
        $downloadIdentifier = session()->get(Constants::DOWNLOAD_JOB_IDENTIFIER);
        $disk               = Storage::disk('downloads');
        $json               = $disk->get($downloadIdentifier);
        try {
            $array = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ImporterErrorException(sprintf('Could not decode download: %s', $e->getMessage()), 0, $e);
        }
        $opposing = [];

        /** @var array $transaction */
        foreach ($array as $accountId => $transactions) {
            Log::debug(sprintf('Now looping account %s', $accountId));
            $total = count($transactions);
            /** @var array $transaction */
            foreach ($transactions as $index => $transaction) {
                Log::debug(sprintf('[%s/%s] Parsing transaction', ($index+1), $total));
                $object = Transaction::fromLocalArray($transaction);
                $opposing[] = (string)$object->getDestinationName();
                $opposing[] = (string)$object->getSourceName();
            }
        }
        $filtered = array_filter(
            $opposing,
            static function (string $value) {
                return '' !== $value;
            }
        );

        return array_unique($filtered);
    }

    /**
     * @return array
     * @throws ApiHttpException
     */
    private function getFireflyIIIAccounts(): array
    {
        $token   = (string) config('importer.access_token');
        $url     = (string) config('importer.url');
        $request = new GetAccountsRequest($url, $token);

        $request->setVerify(config('importer.connection.verify'));
        $request->setTimeOut(config('importer.connection.timeout'));

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
     * @return array
     * @throws ApiHttpException
     */
    private function getFireflyIIICategories(): array
    {
        $token   = (string) config('importer.access_token');
        $url     = (string) config('importer.url');
        $request = new GetCategoriesRequest($url, $token);

        $request->setVerify(config('importer.connection.verify'));
        $request->setTimeOut(config('importer.connection.timeout'));

        /** @var GetCategoriesResponse $result */
        $result = $request->get();
        $return = [];
        foreach ($result as $entry) {
            $id          = (int) $entry->id;
            $return[$id] = $entry->name;
        }
        asort($return);

        return $return;
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
        $result = $request->all();

        $accountMapping  = $result['mapping_accounts'] ?? [];
        $mapping         = [
            'accounts'   => $accountMapping,
        ];

        $accountTypes  = $result['account_type'] ?? [];
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

        return redirect(route('import.sync.index'));
    }
}
