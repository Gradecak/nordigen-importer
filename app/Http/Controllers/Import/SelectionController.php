<?php
/*
 * SelectionController.php
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

namespace App\Http\Controllers\Import;

use App\Http\Controllers\Controller;
use App\Http\Requests\SelectionRequest;
use App\Services\Configuration\Configuration;
use App\Services\Nordigen\Request\ListBanksRequest;
use App\Services\Session\Constants;
use App\Services\Spectre\Response\ErrorResponse;
use App\Services\Storage\StorageService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\View\View;
use JsonException;
use Log;

/**
 * Class SelectionController
 */
class SelectionController extends Controller
{

    /**
     * @return Factory|View
     */
    public function index()
    {
        $countries = config('importer.countries');
        $mainTitle = 'Selection';
        $subTitle  = 'Select your country and the bank you wish to use.';

        // get banks and countries
        $url     = config('importer.nordigen_url');
        $token   = config('importer.nordigen_token');
        $request = new ListBanksRequest($url, $token);
        $request->setTimeOut(config('importer.connection.timeout'));

        $response = $request->get();
        if ($response instanceof ErrorResponse) {
            die('do something!');
        }

        return view('import.001-selection.index', compact('mainTitle', 'subTitle', 'response', 'countries'));
    }

    /**
     * @param Request $request
     */
    public function post(SelectionRequest $request)
    {
        // create a new config thing
        $configuration = Configuration::fromArray([]);
        if (session()->has(Constants::CONFIGURATION)) {
            $configuration = Configuration::fromArray(session()->get(Constants::CONFIGURATION));
        }
        $values = $request->getAll();
        $configuration->setCountry($values['country']);
        $configuration->setBank($values['bank']);

        // save config
        $json = '[]';
        try {
            $json = json_encode($configuration, JSON_THROW_ON_ERROR, 512);
        } catch (JsonException $e) {
            Log::error($e->getMessage());
        }
        StorageService::storeContent($json);

        session()->put(Constants::CONFIGURATION, $configuration->toArray());
        session()->put(Constants::SELECTED_BANK_COUNTRY, 'true');

        // send to ???
        return redirect(route('import.configure.index'));
    }

}
