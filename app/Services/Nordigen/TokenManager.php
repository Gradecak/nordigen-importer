<?php
/*
 * TokenManager.php
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

namespace App\Services\Nordigen;

use App\Exceptions\ImporterErrorException;
use App\Exceptions\ImporterHttpException;
use App\Services\Nordigen\Request\PostNewTokenRequest;
use App\Services\Nordigen\Response\TokenSetResponse;
use App\Services\Session\Constants;
use Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;


class TokenManager
{
    /**
     * @return bool
     */
    public static function hasValidAccessToken(): bool
    {
        Log::debug(sprintf('Now at %s', __METHOD__));
        $hasAccessToken = session()->has(Constants::ACCESS_TOKEN);
        if (false === $hasAccessToken) {
            Log::debug('No token is present, so no valid access token');
            return false;
        }
        $tokenValidity = session()->get(Constants::ACCESS_EXPIRY_TIME) ?? 0;
        Log::debug(sprintf('Token is valid until %s', date('Y-m-d H:i:s', $tokenValidity)));
        $result = time() < $tokenValidity;
        if (false === $result) {
            Log::debug('Token is no longer valid');
            return false;
        }
        Log::debug('Token is valid.');
        return true;
    }

    /**
     * @throws ImporterErrorException
     */
    public static function validateAllTokens(): void
    {
        // is there a valid access and refresh token?
        if (self::hasValidRefreshToken() && self::hasValidAccessToken()) {
            return;
        }

        if (self::hasExpiredRefreshToken()) {
            // refresh!
            self::getFreshAccessToken();
        }

        // get complete set!
        try {
            self::getNewTokenSet();
        } catch (ImporterHttpException $e) {
            throw new ImporterErrorException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @return bool
     */
    public static function hasValidRefreshToken(): bool
    {
        $hasToken = session()->has(Constants::REFRESH_TOKEN);
        if (false === $hasToken) {
            return false;
        }
        $tokenValidity = session()->get(Constants::REFRESH_EXPIRY_TIME) ?? 0;
        return time() < $tokenValidity;
    }

    /**
     * @return bool
     */
    public static function hasExpiredRefreshToken(): bool
    {
        $hasToken = session()->has(Constants::REFRESH_TOKEN);
        if (false === $hasToken) {
            return false;
        }
        die('Here we are 3');
    }

    /**
     * get new token set and store in session
     * @throws ImporterHttpException
     */
    public static function getNewTokenSet(): void
    {
        $client = new PostNewTokenRequest();
        /** @var TokenSetResponse $result */
        $result = $client->post();

        // store in session:
        session()->put(Constants::ACCESS_TOKEN, $result->accessToken);
        session()->put(Constants::REFRESH_TOKEN, $result->refreshToken);

        session()->put(Constants::ACCESS_EXPIRY_TIME, $result->accessExpires);
        session()->put(Constants::REFRESH_EXPIRY_TIME, $result->refreshExpires);
    }

    /**
     * @return string
     * @throws ImporterErrorException
     */
    public static function getAccessToken(): string
    {
        self::validateAllTokens();
        try {
            $token = session()->get(Constants::ACCESS_TOKEN);
        } catch (NotFoundExceptionInterface | ContainerExceptionInterface $e) {
            throw new ImporterErrorException($e->getMessage(), 0, $e);
        }
        return $token;
    }

    /**
     *
     */
    private static function getFreshAccessToken(): void
    {
        die(__METHOD__);
    }

}
