<?php
/*
 * Transaction.php
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

use Carbon\Carbon;
use JetBrains\PhpStorm\Pure;

class Transaction
{
    public string $additionalInformation;
    public string $additionalInformationStructured;
    public string $balanceAfterTransaction;
    public string $bankTransactionCode;
    public Carbon $bookingDate;
    public string $checkId;
    public string $creditorAccount;
    public string $creditorAgent;
    public string $creditorId;
    public string $creditorName;
    public string $currencyExchange;
    public string $debtorAgent;
    public string $debtorName;
    public string $entryReference;
    public string $key;
    public string $mandateId;
    public string $proprietaryBank;
    public string $purposeCode;
    public string $remittanceInformationStructured;
    public string $remittanceInformationStructuredArray;
    public string $remittanceInformationUnstructured;
    public string $remittanceInformationUnstructuredArray;
    public string $transactionId;
    public string $ultimateCreditor;
    public string $ultimateDebtor;
    public Carbon $valueDate;

    // debtorAccount is an array, but is saved as strings
    // iban
    public string $debtorAccountIban;

    // transactionAmount is an array, but is saved as strings
    // amount, currency
    public string $transactionAmount;
    public string $currencyCode;


    /**
     * @param $array
     * @return self
     */
    #[Pure]
    public static function fromArray($array): self
    {
        $object = new self;

        $object->additionalInformation                  = $array['additionalInformation'] ?? '';
        $object->additionalInformationStructured        = $array['additionalInformationStructured'] ?? '';
        $object->balanceAfterTransaction                = $array['balanceAfterTransaction'] ?? '';
        $object->bankTransactionCode                    = $array['bankTransactionCode'] ?? '';
        $object->bookingDate                            = array_key_exists('bookingDate', $array) ? Carbon::createFromFormat('!Y-m-d', $array['bookingDate']) : new Carbon;
        $object->key                                    = $array['key'] ?? '';
        $object->checkId                                = $array['checkId'] ?? '';
        $object->creditorAccount                        = $array['creditorAccount'] ?? '';
        $object->creditorAgent                          = $array['creditorAgent'] ?? '';
        $object->creditorId                             = $array['creditorId'] ?? '';
        $object->creditorName                           = $array['creditorName'] ?? '';
        $object->currencyExchange                       = $array['currencyExchange'] ?? '';
        $object->debtorAgent                            = $array['debtorAgent'] ?? '';
        $object->debtorName                             = $array['debtorName'] ?? '';
        $object->entryReference                         = $array['entryReference'] ?? '';
        $object->mandateId                              = $array['mandateId'] ?? '';
        $object->proprietaryBank                        = $array['proprietaryBank'] ?? '';
        $object->purposeCode                            = $array['purposeCode'] ?? '';
        $object->remittanceInformationStructured        = $array['remittanceInformationStructured'] ?? '';
        $object->remittanceInformationStructuredArray   = $array['remittanceInformationStructuredArray'] ?? '';
        $object->remittanceInformationUnstructured      = $array['remittanceInformationUnstructured'] ?? '';
        $object->remittanceInformationUnstructuredArray = $array['remittanceInformationUnstructuredArray'] ?? '';
        $object->transactionId                          = $array['transactionId'] ?? '';
        $object->ultimateCreditor                       = $array['ultimateCreditor'] ?? '';
        $object->ultimateDebtor                         = $array['ultimateDebtor'] ?? '';
        $object->valueDate                              = array_key_exists('valueDate', $array) ? Carbon::createFromFormat('!Y-m-d', $array['valueDate']) : new Carbon;

        // array values:
        $object->debtorAccountIban = $array['debtorAccount']['iban'] ?? '';

        $object->transactionAmount = $array['transactionAmount']['amount'] ?? '';
        $object->currencyCode      = $array['transactionAmount']['currency'] ?? '';

        return $object;
    }
}
