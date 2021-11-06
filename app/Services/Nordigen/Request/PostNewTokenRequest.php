<?php
/*
 * PostNewTokenRequest.php
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

namespace App\Services\Nordigen\Request;

use App\Services\Nordigen\Response\Response;
use App\Services\Nordigen\Response\TokenSetResponse;
use GuzzleHttp\Client;

/**
 * Class PostNewTokenRequest
 */
class PostNewTokenRequest extends Request
{

    /**
     * @inheritDoc
     */
    public function get(): Response
    {
    }

    /**
     * @inheritDoc
     */
    public function post(): Response
    {
        $url    = sprintf('%s/%s', config('importer.nordigen_url'), 'api/v2/token/new/');
        $client = new Client;

        $res = $client->post($url,
                      [
                          'json'    => [
                              'secret_id'  => config('importer.nordigen_id'),
                              'secret_key' => config('importer.nordigen_key'),
                          ],
                          'headers' => [
                              'accept'       => 'application/json',
                              'content-type' => 'application/json',
                              'user-agent'   => sprintf('Firefly III Nordigen importer / %s / %s', config('importer.version'), config('auth.line_a')),
                          ],
                      ]
        );
        $body = (string)$res->getBody();
        $json = json_decode($body, true, JSON_THROW_ON_ERROR);
        return new TokenSetResponse($json);
    }

    /**
     * @inheritDoc
     */
    public function put(): Response
    {
    }
}
