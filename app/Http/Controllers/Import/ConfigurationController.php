<?php
/*
 * ConfigurationController.php
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
use App\Exceptions\ImporterHttpException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ConfigurationPostRequest;
use App\Services\Configuration\Configuration;
use App\Services\FireflyIII\Services\AssetAccountCollector;
use App\Services\Nordigen\Model\Account;
use App\Services\Nordigen\Request\ListAccountsRequest;
use App\Services\Nordigen\Response\ListAccountsResponse;
use App\Services\Nordigen\Services\AccountInformationCollector;
use App\Services\Nordigen\TokenManager;
use App\Services\Session\Constants;
use Cache;
use GrumpyDictator\FFIIIApiSupport\Model\Account as FFAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use JsonException;
use Log;

/**
 * Class ConfigurationController
 */
class ConfigurationController extends Controller
{
    /**
     * @param Request $request
     */
    public function index(Request $request)
    {
        Log::debug(sprintf('Now at %s', __METHOD__));
        $mainTitle = 'Import from Nordigen';
        $subTitle  = 'Configure your Nordigen import';
        $pageTitle = 'Configuration';

        $configuration = Configuration::fromArray([]);
        if (session()->has(Constants::CONFIGURATION)) {
            $configuration = Configuration::fromArray(session()->get(Constants::CONFIGURATION));
        }

        // list all accounts in Nordigen:
        $reference        = $configuration->getRequisition(session()->get(Constants::REQUISITION_REFERENCE));
        $nordigenAccounts = $this->getNordigenAccounts($reference);
        $fireflyAccounts  = AssetAccountCollector::collectAssetAccounts();

        // merge if necessary (will block some options)
        $nordigenAccounts = $this->mergeAccountLists($nordigenAccounts, $fireflyAccounts);

        // show index.
        return view('import.003-configuration.index', compact('fireflyAccounts', 'nordigenAccounts', 'configuration', 'mainTitle', 'subTitle', 'pageTitle'));
    }


    /**
     * @return Response
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function download(): Response
    {
        // do something
        $result = '';
        $config = Configuration::fromArray(session()->get(Constants::CONFIGURATION))->toArray();
        try {
            $result = json_encode($config, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT, 512);
        } catch (JsonException $e) {
            Log::error($e->getMessage());
        }

        $response = response($result);
        $name     = sprintf('nordigen_import_config_%s.json', date('Y-m-d'));
        $response->header('Content-disposition', 'attachment; filename=' . $name)
                 ->header('Content-Type', 'application/json')
                 ->header('Content-Description', 'File Transfer')
                 ->header('Connection', 'Keep-Alive')
                 ->header('Expires', '0')
                 ->header('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
                 ->header('Pragma', 'public')
                 ->header('Content-Length', strlen($result));

        return $response;
    }


    /**
     * @param Request $request
     */
    public function post(ConfigurationPostRequest $request)
    {
        app('log')->debug(sprintf('Now at %s', __METHOD__));

        // get config from request
        $fromRequest = $request->getAll();

        // get config from session
        $configuration = Configuration::fromArray([]);
        if (session()->has(Constants::CONFIGURATION)) {
            $configuration = Configuration::fromArray(session()->get(Constants::CONFIGURATION));
        }

        // update config, all except accounts
        $configuration->setDateRange($fromRequest['date_range']);
        $configuration->setDateRangeNumber($fromRequest['date_range_number']);
        $configuration->setDateRangeUnit($fromRequest['date_range_unit']);

        if (null !== $fromRequest['date_not_before']) {
            $configuration->setDateNotBefore($fromRequest['date_not_before']->format('Y-m-d'));
        }
        if (null !== $fromRequest['date_not_after']) {
            $configuration->setDateNotAfter($fromRequest['date_not_after']->format('Y-m-d'));
        }

        $configuration->setRules($fromRequest['rules']);
        $configuration->setAddImportTag($fromRequest['add_import_tag']);
        $configuration->setIgnoreDuplicateTransactions($fromRequest['ignore_duplicate_transactions']);
        $configuration->setDoMapping($fromRequest['do_mapping']);
        $configuration->setSkipForm($fromRequest['skip_form']);

        // loop accounts:
        $accounts = [];
        foreach (array_keys($fromRequest['do_import']) as $identifier) {
            if (isset($fromRequest['accounts'][$identifier])) {
                $accounts[$identifier] = (int) $fromRequest['accounts'][$identifier];
            }
        }
        $configuration->setAccounts($accounts);
        $configuration->updateDateRange();

        session()->put(Constants::CONFIGURATION, $configuration->toArray());

        // set config as complete.
        session()->put(Constants::CONFIG_COMPLETE_INDICATOR, true);

        // redirect to import things?
        return redirect()->route('import.download.index');
    }

    /**
     * List Nordigen accounts with account details, balances, and 2 transactions (if present)
     * @return array
     * @throws ImporterErrorException
     */
    private function getNordigenAccounts(string $identifier): array
    {
        if (Cache::has($identifier)) {
            $result = Cache::get($identifier);
            $return = [];
            foreach ($result as $arr) {
                $return[] = Account::fromLocalArray($arr);
            }
            Log::debug('Grab accounts from cache', $result);
            return $return;
        }
        Log::debug(sprintf('Now in %s', __METHOD__));
        // get banks and countries
        $accessToken = TokenManager::getAccessToken();
        $url         = config('importer.nordigen_url');
        $request     = new ListAccountsRequest($url, $identifier, $accessToken);
        /** @var ListAccountsResponse $response */
        try {
            $response = $request->get();
        } catch (ImporterErrorException $e) {
        } catch (ImporterHttpException $e) {
            throw new ImporterErrorException($e->getMessage(), 0, $e);
        }
        $total  = count($response);
        $return = [];
        $cache  = [];
        Log::debug(sprintf('Found %d accounts.', $total));

        /** @var Account $account */
        foreach ($response as $index => $account) {
            Log::debug(sprintf('[%d/%d] Now collecting information for account %s', ($index + 1), $total, $account->getIdentifier()));
            $account  = AccountInformationCollector::collectInformation($account);
            $return[] = $account;
            $cache[]  = $account->toLocalArray();
        }
        Cache::put($identifier, $cache, 1800); // half an hour
        return $return;
    }

    /**
     * @param array $nordigen
     * @param array $firefly
     * @return array
     *
     * TODO move to some helper.
     */
    private function mergeAccountLists(array $nordigen, array $firefly): array
    {
        Log::debug('Now creating account lists.');
        $return = [];
        /** @var Account $nordigenAccount */
        foreach ($nordigen as $nordigenAccount) {
            Log::debug(sprintf('Now working on account "%s": "%s"', $nordigenAccount->getName(), $nordigenAccount->getIdentifier()));
            $iban     = $nordigenAccount->getIban();
            $currency = $nordigenAccount->getCurrency();
            $entry    = [
                'nordigen' => $nordigenAccount,
                'firefly'  => [],
            ];

            // only iban?
            $filteredByIban = $this->filterByIban($firefly, $iban);

            if (1 === count($filteredByIban)) {
                Log::debug(sprintf('This account (%s) has a single Firefly III counter part (#%d, "%s", same IBAN), so will use that one.', $iban, $filteredByIban[0]->id, $filteredByIban[0]->name));
                $entry['firefly'] = $filteredByIban;
                $return[]         = $entry;
                continue;
            }
            Log::debug(sprintf('Found %d accounts with the same IBAN ("%s")', count($filteredByIban), $iban));

            // only currency?
            $filteredByCurrency = $this->filterByCurrency($firefly, $currency);

            if (count($filteredByCurrency) > 0) {
                Log::debug(sprintf('This account (%s) has some Firefly III counter parts with the same currency so will only use those.', $currency));
                $entry['firefly'] = $filteredByCurrency;
                $return[]         = $entry;
                continue;
            }
            Log::debug('No special filtering on the Firefly III account list.');
            $entry['firefly'] = $firefly;
            $return[]         = $entry;
        }
        return $return;
    }

    /**
     * @param array  $firefly
     * @param string $iban
     * @return array
     */
    private function filterByIban(array $firefly, string $iban): array
    {
        if ('' === $iban) {
            return [];
        }
        $result = [];
        /** @var FFAccount $account */
        foreach ($firefly as $account) {
            if ($iban === $account->iban) {
                $result[] = $account;
            }
        }
        return $result;
    }

    /**
     * @param array  $firefly
     * @param string $currency
     * @return array
     */
    private function filterByCurrency(array $firefly, string $currency): array
    {
        if ('' === $currency) {
            return [];
        }
        $result = [];
        /** @var FFAccount $account */
        foreach ($firefly as $account) {
            if ($currency === $account->currencyCode) {
                $result[] = $account;
            }
        }
        return $result;
    }

}
