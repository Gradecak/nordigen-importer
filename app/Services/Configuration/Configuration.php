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

use Carbon\Carbon;
use Log;

/**
 * Class Configuration
 */
class Configuration
{
    /** @var int */
    public const VERSION = 1;
    private string  $country;
    private string  $bank;
    private int     $version;
    private array   $requisitions;
    private ?string $dateRange;
    private ?int    $dateRangeNumber;
    private ?string $dateRangeUnit;
    private ?string $dateNotBefore;
    private ?string $dateNotAfter;
    private bool    $rules;
    private bool    $skipForm;
    private bool    $addImportTag;
    private bool    $ignoreDuplicateTransactions;
    private bool    $doMapping;
    private array   $accounts;

    /**
     * Configuration constructor.
     */
    private function __construct()
    {
        $this->country                     = 'XX';
        $this->bank                        = 'XX';
        $this->requisitions                = [];
        $this->dateRange                   = 'all';
        $this->dateRangeNumber             = 30;
        $this->dateRangeUnit               = 'd';
        $this->dateNotBefore               = '';
        $this->dateNotAfter                = '';
        $this->rules                       = true;
        $this->skipForm                    = false;
        $this->addImportTag                = true;
        $this->ignoreDuplicateTransactions = true;
        $this->doMapping                   = false;
        $this->accounts                    = [];

        $this->version = self::VERSION;
    }

    /**
     * @return bool
     */
    public function isIgnoreDuplicateTransactions(): bool
    {
        return $this->ignoreDuplicateTransactions;
    }


    /**
     * @return string|null
     */
    public function getDateNotBefore(): ?string
    {
        return $this->dateNotBefore;
    }

    /**
     * @return string|null
     */
    public function getDateNotAfter(): ?string
    {
        return $this->dateNotAfter;
    }

    /**
     * @return bool
     */
    public function isRules(): bool
    {
        return $this->rules;
    }

    /**
     * @return bool
     */
    public function isSkipForm(): bool
    {
        return $this->skipForm;
    }


    /**
     * @return string
     */
    public function getBank(): string
    {
        return $this->bank;
    }

    /**
     * @param string $identifier
     */
    public function addRequisition(string $key, string $identifier)
    {
        $this->requisitions[$key] = $identifier;
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function getRequisition(string $key): ?string
    {
        return array_key_exists($key, $this->requisitions) ? $this->requisitions[$key] : null;
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
        $version                             = $array['version'] ?? 1;
        $object                              = new self;
        $object->version                     = $version;
        $object->country                     = $array['country'] ?? 'XX';
        $object->bank                        = $array['bank'] ?? 'XX';
        $object->requisitions                = $array['requisitions'] ?? [];
        $object->dateRange                   = $array['date_range'] ?? 'all';
        $object->dateRangeNumber             = $array['date_range_number'] ?? 30;
        $object->dateRangeUnit               = $array['date_range_unit'] ?? 'd';
        $object->dateNotBefore               = $array['date_not_before'] ?? '';
        $object->dateNotAfter                = $array['date_not_after'] ?? '';
        $object->rules                       = $array['rules'] ?? true;
        $object->skipForm                    = $array['skip_form'] ?? false;
        $object->addImportTag                = $array['add_import_tag'] ?? true;
        $object->ignoreDuplicateTransactions = $array['ignore_duplicate_transactions'] ?? true;
        $object->doMapping                   = $array['do_mapping'] ?? false;
        $object->accounts                    = $array['accounts'] ?? [];


        return $object;
    }

    /**
     * @param bool $rules
     */
    public function setRules(bool $rules): void
    {
        $this->rules = $rules;
    }

    /**
     * @param bool $skipForm
     */
    public function setSkipForm(bool $skipForm): void
    {
        $this->skipForm = $skipForm;
    }

    /**
     * @param string|null $dateNotBefore
     */
    public function setDateNotBefore(?string $dateNotBefore): void
    {
        $this->dateNotBefore = $dateNotBefore;
    }

    /**
     * @param string|null $dateNotAfter
     */
    public function setDateNotAfter(?string $dateNotAfter): void
    {
        $this->dateNotAfter = $dateNotAfter;
    }

    /**
     * @param string|null $dateRange
     */
    public function setDateRange(?string $dateRange): void
    {
        $this->dateRange = $dateRange;
    }

    /**
     * @param int|null $dateRangeNumber
     */
    public function setDateRangeNumber(?int $dateRangeNumber): void
    {
        $this->dateRangeNumber = $dateRangeNumber;
    }

    /**
     * @param string|null $dateRangeUnit
     */
    public function setDateRangeUnit(?string $dateRangeUnit): void
    {
        $this->dateRangeUnit = $dateRangeUnit;
    }

    /**
     * @param bool $doMapping
     */
    public function setDoMapping(bool $doMapping): void
    {
        $this->doMapping = $doMapping;
    }

    /**
     * @param bool $addImportTag
     */
    public function setAddImportTag(bool $addImportTag): void
    {
        $this->addImportTag = $addImportTag;
    }

    /**
     * @param bool $ignoreDuplicateTransactions
     */
    public function setIgnoreDuplicateTransactions(bool $ignoreDuplicateTransactions): void
    {
        $this->ignoreDuplicateTransactions = $ignoreDuplicateTransactions;
    }

    /**
     * @param array $accounts
     */
    public function setAccounts(array $accounts): void
    {
        $this->accounts = $accounts;
    }


    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'version'                       => $this->version,
            'country'                       => $this->country,
            'bank'                          => $this->bank,
            'requisitions'                  => $this->requisitions,
            'date_range'                    => $this->dateRange,
            'date_range_number'             => $this->dateRangeNumber,
            'date_range_unit'               => $this->dateRangeUnit,
            'date_not_before'               => $this->dateNotBefore,
            'date_not_after'                => $this->dateNotAfter,
            'rules'                         => $this->rules,
            'skip_form'                     => $this->skipForm,
            'add_import_tag'                => $this->addImportTag,
            'ignore_duplicate_transactions' => $this->ignoreDuplicateTransactions,
            'do_mapping'                    => $this->doMapping,
            'accounts'                      => $this->accounts,
        ];
    }

    /**
     * @return bool
     */
    public function isAddImportTag(): bool
    {
        return $this->addImportTag;
    }


    /**
     * @return string|null
     */
    public function getDateRange(): ?string
    {
        return $this->dateRange;
    }

    /**
     * @return int|null
     */
    public function getDateRangeNumber(): ?int
    {
        return $this->dateRangeNumber;
    }

    /**
     * @return string|null
     */
    public function getDateRangeUnit(): ?string
    {
        return $this->dateRangeUnit;
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

    /**
     *
     */
    public function updateDateRange(): void
    {
        Log::debug('Now in updateDateRange()');
        // set date and time:
        switch ($this->dateRange) {
            case 'all':
                Log::debug('Range is null, set all to NULL.');
                $this->dateRangeUnit   = 'd';
                $this->dateRangeNumber = 30;
                $this->dateNotBefore   = '';
                $this->dateNotAfter    = '';
                break;
            case 'partial':
                Log::debug('Range is partial, after is NULL, dateNotBefore will be calculated.');
                $this->dateNotAfter  = '';
                $this->dateNotBefore = self::calcDateNotBefore($this->dateRangeUnit, $this->dateRangeNumber);
                Log::debug(sprintf('dateNotBefore is now "%s"', $this->dateNotBefore));
                break;
            case 'range':
                Log::debug('Range is "range", both will be created from a string.');
                $before = $this->dateNotBefore; // string
                $after  = $this->dateNotAfter; // string
                if (null !== $before) {
                    $before = Carbon::createFromFormat('Y-m-d', $before);
                }
                if (null !== $after) {
                    $after = Carbon::createFromFormat('Y-m-d', $after);
                }

                if (null !== $before && null !== $after && $before > $after) {
                    [$before, $after] = [$after, $before];
                }

                $this->dateNotBefore = null === $before ? '' : $before->format('Y-m-d');
                $this->dateNotAfter  = null === $after ? '' : $after->format('Y-m-d');
                Log::debug(sprintf('dateNotBefore is now "%s", dateNotAfter is "%s"', $this->dateNotBefore, $this->dateNotAfter));
        }
    }

    /**
     * @return bool
     */
    public function isDoMapping(): bool
    {
        return $this->doMapping;
    }



    /**
     * @param string $unit
     * @param int    $number
     *
     * @return string|null
     */
    private static function calcDateNotBefore(string $unit, int $number): ?string
    {
        $functions = [
            'd' => 'subDays',
            'w' => 'subWeeks',
            'm' => 'subMonths',
            'y' => 'subYears',
        ];
        if (isset($functions[$unit])) {
            $today    = Carbon::now();
            $function = $functions[$unit];
            $today->$function($number);

            return $today->format('Y-m-d');
        }
        app('log')->error(sprintf('Could not parse date setting. Unknown key "%s"', $unit));

        return null;
    }
}
