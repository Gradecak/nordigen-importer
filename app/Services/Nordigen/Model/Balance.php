<?php
/*
 * Balance.php
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

namespace App\Services\Nordigen\Model;

use Log;

/**
 * Class Balance
 */
class Balance
{
    public string $amount;
    public string $currency;
    public string $type;
    public string $date;
    public string $lastChangeDateTime;

    /**
     * @param array $data
     * @return static
     */
    public static function createFromArray(array $data): self
    {
        Log::debug('Create Balance from array', $data);
        $self                     = new self;
        $self->amount             = $data['balanceAmount']['amount'];
        $self->currency           = $data['balanceAmount']['currency'];
        $self->type               = $data['balanceType'];
        $self->date               = $data['referenceDate'] ?? '';
        $self->lastChangeDateTime = $data['lastChangeDateTime'] ?? '';

        if ('' === $self->date && '' === $self->lastChangeDateTime) {
            Log::warning('Balance has invalid date!');
        }
        return $self;
    }

    /**
     * @return array
     */
    public function toLocalArray(): array
    {
        return [
            'amount'                => $this->amount,
            'currency'              => $this->currency,
            'type'                  => $this->type,
            'date'                  => $this->date,
            'last_change_date_time' => $this->lastChangeDateTime,
        ];
    }

    /**
     * @return $this
     */
    public static function fromLocalArray(array $array): self
    {
        $object                     = new self;
        $object->amount             = $array['amount'];
        $object->currency           = $array['currency'];
        $object->type               = $array['type'];
        $object->date               = $array['date'];
        $object->lastChangeDateTime = $array['last_change_date_time'];
        return $object;
    }
}
