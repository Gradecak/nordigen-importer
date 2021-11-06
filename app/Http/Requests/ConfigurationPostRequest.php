<?php
/*
 * ConfigurationPostRequest.php
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

namespace App\Http\Requests;


use Illuminate\Validation\Validator;

/**
 * Class ConfigurationPostRequest
 */
class ConfigurationPostRequest extends Request
{
    /**
     * @return array
     */
    public function getAll(): array
    {
        // parse entire config file.
        $doImport = $this->get('do_import') ?? [];

        return [
            'do_import'                     => $doImport,
            'accounts'                      => $this->get('accounts'),
            'date_range'                    => $this->string('date_range'),
            'date_range_number'             => $this->integer('date_range_number'),
            'date_range_unit'               => $this->string('date_range_unit'),
            'date_not_before'               => $this->date('date_not_before'),
            'date_not_after'                => $this->date('date_not_after'),
            'rules'                         => $this->convertBoolean($this->get('rules')),
            'add_import_tag'                => $this->convertBoolean($this->get('add_import_tag')),
            'ignore_duplicate_transactions' => $this->convertBoolean($this->get('ignore_duplicate_transactions')),
            'do_mapping'                    => $this->convertBoolean($this->get('do_mapping')),
            'skip_form'                     => $this->convertBoolean($this->get('skip_form')),
        ];
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            //'some_weird_field' => 'required',
            'do_import.*'                   => 'numeric',
            'accounts.*'                    => 'numeric',
            'date_range'                    => 'required|in:all,partial,range',
            'date_range_number'             => 'numeric|between:1,365',
            'date_range_unit'               => 'required|in:d,w,m,y',
            'date_not_before'               => 'date|nullable',
            'date_not_after'                => 'date|nullable',
            'rules'                         => 'numeric|integer|between:0,1',
            'add_import_tag'                => 'numeric|integer|between:0,1',
            'ignore_duplicate_transactions' => 'numeric|integer|between:0,1',
            'do_mapping'                    => 'numeric|integer|between:0,1',
            'skip_form'                     => 'numeric|integer|between:0,1',
        ];
    }


    /**
     * Configure the validator instance with special rules for after the basic validation rules.
     *
     * @param Validator $validator
     *
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(
            function (Validator $validator) {
                // validate all account info
                $data = $validator->getData();
                $doImport = $data['do_import'] ?? [];
                if (0 === count($doImport)) {
                    $validator->errors()->add('do_import', 'You must select at least one account to import from.');
                }
            }
        );
    }

}
