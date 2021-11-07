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
use DateTimeInterface;
use Log;

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

    // other custom fields
    public string $accountIdentifier;


    /**
     * @param $array
     * @return self
     */
    public static function fromArray($array): self
    {
        $object = new self;

        $object->additionalInformation                  = $array['additionalInformation'] ?? '';
        $object->additionalInformationStructured        = $array['additionalInformationStructured'] ?? '';
        $object->balanceAfterTransaction                = $array['balanceAfterTransaction'] ?? '';
        $object->bankTransactionCode                    = $array['bankTransactionCode'] ?? '';
        $object->bookingDate                            = array_key_exists('bookingDate', $array) ? Carbon::createFromFormat('!Y-m-d', $array['bookingDate'], config('app.timezone')) : new Carbon(config('app.timezone'));
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
        $object->valueDate                              = array_key_exists('valueDate', $array) ? Carbon::createFromFormat('!Y-m-d', $array['valueDate'], config('app.timezone')) : new Carbon(config('app.timezone'));

        // array values:
        $object->debtorAccountIban = $array['debtorAccount']['iban'] ?? '';
        $object->transactionAmount = $array['transactionAmount']['amount'] ?? '';
        $object->currencyCode      = $array['transactionAmount']['currency'] ?? '';

        // other fields:
        $object->accountIdentifier = '';

        return $object;
    }

    /**
     * Return transaction description, which depends on the values in the object:
     * @return string
     */
    public function getDescription(): string
    {
        $description = '';
        if ('' !== $this->remittanceInformationUnstructured) {
            $description = $this->remittanceInformationUnstructured;
        }

        return $description;
    }

    /**
     * Return name of the destination account. Depends also on the amount
     * TODO incorporate logic for amount.
     *
     * @return string|null
     */
    public function getDestinationName(): ?string {
        if('' !== $this->debtorName) {
            return $this->debtorName;
        }
        Log::warning(sprintf('Transaction "%s" has no destination account information.', $this->transactionId));
        return null;
    }

    /**
     * Return name of the source account. Depends also on the amount
     * TODO incorporate logic for amount.
     *
     * @return string|null
     */
    public function getSourceName(): ?string {
        if('' !== $this->creditorName) {
            return $this->creditorName;
        }
        Log::warning(sprintf('Transaction "%s" has no source account information.', $this->transactionId));
        return null;
    }

    /**
     * Call this "toLocalArray" because we want to confusion with "fromArray", which is really based
     * on Nordigen information. Likewise there is also "fromLocalArray".
     * @return array
     */
    public function toLocalArray(): array
    {
        $return = [
            'additional_information'                    => $this->additionalInformation,
            'additional_information_structured'         => $this->additionalInformationStructured,
            'balance_after_transaction'                 => $this->balanceAfterTransaction,
            'bank_transaction_code'                     => $this->bankTransactionCode,
            'booking_date'                              => $this->bookingDate->toW3cString(),
            'check_id'                                  => $this->checkId,
            'creditor_account'                          => $this->creditorAccount,
            'creditor_agent'                            => $this->creditorAgent,
            'creditor_id'                               => $this->creditorId,
            'creditor_name'                             => $this->creditorName,
            'currency_exchange'                         => $this->currencyExchange,
            'debtor_agent'                              => $this->debtorAgent,
            'debtor_name'                               => $this->debtorName,
            'entry_reference'                           => $this->entryReference,
            'key'                                       => $this->key,
            'mandate_id'                                => $this->mandateId,
            'proprietary_bank'                          => $this->proprietaryBank,
            'purpose_code'                              => $this->purposeCode,
            'remittance_information_structured'         => $this->remittanceInformationStructured,
            'remittance_information_structured_array'   => $this->remittanceInformationStructuredArray,
            'remittance_information_unstructured'       => $this->remittanceInformationUnstructured,
            'remittance_information_unstructured_array' => $this->remittanceInformationUnstructuredArray,
            'transaction_id'                            => $this->transactionId,
            'ultimate_creditor'                         => $this->ultimateCreditor,
            'ultimate_debtor'                           => $this->ultimateDebtor,
            'value_date'                                => $this->valueDate->toW3cString(),
            'account_identifier'                        => $this->accountIdentifier,
            'debtor_account'                            => [],
            'transaction_amount'                        => [
                'amount'   => $this->transactionAmount,
                'currency' => $this->currencyCode,
            ],
        ];
        if ('' !== $this->debtorAccountIban) {
            // debtor is an array
            $return['debtor_account'] = ['iban' => $this->debtorAccountIban];
        }

        return $return;
    }

    public static function fromLocalArray(array $array): self
    {
        $object = new self;

        $object->additionalInformation                  = $array['additional_information'];
        $object->additionalInformationStructured        = $array['additional_information_structured'];
        $object->balanceAfterTransaction                = $array['balance_after_transaction'];
        $object->bankTransactionCode                    = $array['bank_transaction_code'];
        $object->bookingDate                            = Carbon::createFromFormat(DateTimeInterface::W3C, $array['booking_date']);
        $object->checkId                                = $array['check_id'];
        $object->creditorAccount                        = $array['creditor_account'];
        $object->creditorAgent                          = $array['creditor_agent'];
        $object->creditorId                             = $array['creditor_id'];
        $object->creditorName                           = $array['creditor_name'];
        $object->currencyExchange                       = $array['currency_exchange'];
        $object->debtorAgent                            = $array['debtor_agent'];
        $object->debtorName                             = $array['debtor_name'];
        $object->entryReference                         = $array['entry_reference'];
        $object->key                                    = $array['key'];
        $object->mandateId                              = $array['mandate_id'];
        $object->proprietaryBank                        = $array['proprietary_bank'];
        $object->purposeCode                            = $array['purpose_code'];
        $object->remittanceInformationStructured        = $array['remittance_information_structured'];
        $object->remittanceInformationStructuredArray   = $array['remittance_information_structured_array'];
        $object->remittanceInformationUnstructured      = $array['remittance_information_unstructured'];
        $object->remittanceInformationUnstructuredArray = $array['remittance_information_unstructured_array'];
        $object->transactionId                          = $array['transaction_id'];
        $object->ultimateCreditor                       = $array['ultimate_creditor'];
        $object->ultimateDebtor                         = $array['ultimate_debtor'];
        $object->valueDate                              = Carbon::createFromFormat(DateTimeInterface::W3C, $array['value_date']);
        $object->debtorAccountIban                      = array_key_exists('iban', $array['debtor_account']) ? $array['debtor_account']['iban'] : '';
        $object->transactionAmount                      = $array['transaction_amount']['amount'];
        $object->currencyCode                           = $array['transaction_amount']['currency'];
        $object->accountIdentifier                      = $array['account_identifier'];

        //$object-> = $array[''];


        return $object;
    }
}
