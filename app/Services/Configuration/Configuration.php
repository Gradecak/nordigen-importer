<?php
/*
 * Configuration.php
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

namespace App\Services\Configuration;

use Log;

/**
 * Class Configuration
 */
class Configuration
{
    /** @var int */
    public const VERSION = 1;
    private string $country;
    private string $bank;
    private int    $version;

    /**
     * Configuration constructor.
     */
    private function __construct()
    {
        $this->country = 'XX';
        $this->bank    = 'XX';
        $this->version = self::VERSION;
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public static function fromFile(array $data): self
    {
        Log::debug('Now in Configuration::fromFile', $data);
        return self::fromArray($data);
    }

    /**
     * @param array $array
     *
     * @return static
     */
    public static function fromArray(array $array): self
    {
        $version         = $array['version'] ?? 1;
        $object          = new self;
        $object->version = $version;
        $object->country = $array['country'] ?? 'XX';
        $object->bank    = $array['bank'] ?? 'XX';

        return $object;
    }


    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'country' => $this->country,
            'bank'    => $this->bank,
        ];
    }

    /**
     * @param string $country
     */
    public function setCountry(string $country): void
    {
        $this->country = $country;
    }

    /**
     * @param string $bank
     */
    public function setBank(string $bank): void
    {
        $this->bank = $bank;
    }






}
