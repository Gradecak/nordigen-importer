<?php
/*
 * TokenController.php
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
/**
 * TokenController.php
 * Copyright (c) 2020 james@firefly-iii.org
 *
 * This file is part of the Firefly III CSV importer
 * (https://github.com/firefly-iii/csv-importer).
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

namespace App\Http\Controllers;

use App\Services\Nordigen\Request\ListBanksRequest;
use App\Services\Nordigen\Response\ErrorResponse;
use App\Services\Nordigen\TokenManager;
use GrumpyDictator\FFIIIApiSupport\Exceptions\ApiHttpException;
use GrumpyDictator\FFIIIApiSupport\Request\SystemInformationRequest;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;
use Log;

/**
 * Class TokenController
 */
class TokenController extends Controller
{
    /**
     * Check if the Firefly III API responds properly.
     *
     * @return JsonResponse
     */
    public function doValidate(): JsonResponse
    {
        Log::debug(sprintf('Now at %s', __METHOD__));
        $response = ['result' => 'OK', 'message' => null];
        $error    = $this->verifyFireflyIII();
        if (null !== $error) {
            // send user error:
            return response()->json(['result' => 'NOK', 'message' => $error]);
        }

        // Nordigen:
        $error = $this->verifyNordigen();
        if (null !== $error) {
            // send user error:
            return response()->json(['result' => 'NOK', 'message' => $error]);
        }

        return response()->json($response);
    }

    /**
     * @return string|null
     */
    private function verifyFireflyIII(): ?string
    {
        Log::debug(sprintf('Now at %s', __METHOD__));
        // verify access
        $url     = (string)config('importer.url');
        $token   = (string)config('importer.access_token');
        $request = new SystemInformationRequest($url, $token);

        $request->setVerify(config('importer.connection.verify'));
        $request->setTimeOut(config('importer.connection.timeout'));

        try {
            $result = $request->get();
        } catch (ApiHttpException $e) {
            return $e->getMessage();
        }
        // -1 = OK (minimum is smaller)
        // 0 = OK (same version)
        // 1 = NOK (too low a version)

        // verify version:
        $minimum = (string)config('importer.minimum_version');
        $compare = version_compare($minimum, $result->version);
        if (1 === $compare) {
            return sprintf(
                'Your Firefly III version %s is below the minimum required version %s',
                $result->version, $minimum
            );
        }

        return null;
    }

    /**
     * @return string|null
     */
    private function verifyNordigen(): ?string
    {
        Log::debug(sprintf('Now at %s', __METHOD__));

        // is there a valid access and refresh token?
        if(TokenManager::hasValidRefreshToken() && TokenManager::hasValidAccessToken()) {
            return null;
        }

        if(TokenManager::hasExpiredRefreshToken()) {
            // refresh!
            TokenManager::getFreshAccessToken();
        }

        // get complete set!
        TokenManager::getNewTokenSet();
        return null;
    }

    /**
     * Same thing but not over JSON.
     *
     * @return Factory|RedirectResponse|Redirector|View
     */
    public function index()
    {
        Log::debug(sprintf('Now at %s', __METHOD__));
        $pageTitle = 'Token error';

        // verify Firefly III
        $errorMessage = $this->verifyFireflyIII();
        if (null !== $errorMessage) {
            return view('token.index', compact('errorMessage', 'pageTitle'));
        }

        // verify Spectre:
        $errorMessage = $this->verifyNordigen();

        if (null !== $errorMessage) {
            return view('token.index', compact('errorMessage', 'pageTitle'));
        }

        return redirect(route('index'));
    }

}
