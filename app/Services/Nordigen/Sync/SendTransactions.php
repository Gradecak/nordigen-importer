<?php
/*
 * SendTransactions.php
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

namespace App\Services\Nordigen\Sync;

use App\Services\Configuration\Configuration;
use App\Services\Nordigen\Sync\JobStatus\ProgressInformation;
use GrumpyDictator\FFIIIApiSupport\Exceptions\ApiHttpException;
use GrumpyDictator\FFIIIApiSupport\Model\Transaction;
use GrumpyDictator\FFIIIApiSupport\Model\TransactionGroup;
use GrumpyDictator\FFIIIApiSupport\Request\PostTagRequest;
use GrumpyDictator\FFIIIApiSupport\Request\PostTransactionRequest;
use GrumpyDictator\FFIIIApiSupport\Request\PutTransactionRequest;
use GrumpyDictator\FFIIIApiSupport\Response\PostTagResponse;
use GrumpyDictator\FFIIIApiSupport\Response\PostTransactionResponse;
use GrumpyDictator\FFIIIApiSupport\Response\ValidationErrorResponse;
use Log;

/**
 * Class SendTransactions.
 */
class SendTransactions
{
    use ProgressInformation;

    private bool          $addTag;
    private Configuration $configuration;
    private string        $rootURL;
    private string        $tag;
    private string        $tagDate;

    /**
     * @param array $transactions
     *
     * @return array
     */
    public function send(array $transactions): array
    {
        Log::debug(sprintf('Now in %s', __METHOD__));
        // create the tag, to be used later on.
        $this->tag     = sprintf('Nordigen Import on %s', date('Y-m-d \@ H:i'));
        $this->tagDate = date('Y-m-d');
        $this->createTag();

        $this->rootURL = config('importer.url');
        if ('' !== (string) config('importer.vanity_url')) {
            $this->rootURL = config('importer.vanity_url');
        }
        Log::debug(sprintf('The root URL is "%s"', $this->rootURL));

        $url   = (string) config('importer.url');
        $token = (string) config('importer.access_token');
        $total = count($transactions);
        /**
         * @var int   $index
         * @var array $transaction
         */
        foreach ($transactions as $index => $transaction) {
            app('log')->debug(sprintf('[%d/%d] Trying to send transaction.', ($index + 1), $total), $transaction);
            $group = $this->sendTransaction($url, $token, $index, $transaction);
            if (null !== $group) {
                app('log')->debug(sprintf('[%d/%d] Group exists, add tag.', ($index + 1), $total));
                $this->addTagToGroup($group);
            }
            app('log')->debug(sprintf('[%d/%d] Done sending transaction.', ($index + 1), $total));
        }
        Log::debug(sprintf('Done with %s', __METHOD__));
        return [];
    }

    /**
     *
     */
    private function createTag(): void
    {
        if (false === $this->addTag) {
            Log::debug('Not instructed to add a tag, so will not create one.');

            return;
        }
        $url     = (string) config('importer.url');
        $token   = (string) config('importer.access_token');
        $request = new PostTagRequest($url, $token);
        $request->setVerify(config('importer.connection.verify'));
        $request->setTimeOut(config('importer.connection.timeout'));
        $body = [
            'tag'  => $this->tag,
            'date' => $this->tagDate,
        ];
        $request->setBody($body);

        try {
            /** @var PostTagResponse $response */
            $response = $request->post();
        } catch (ApiHttpException $e) {
            Log::error(sprintf('Could not create tag. %s', $e->getMessage()));

            return;
        }
        if ($response instanceof ValidationErrorResponse) {
            Log::error($response->errors);

            return;
        }
        if (null !== $response->getTag()) {
            Log::info(sprintf('Created tag #%d "%s"', $response->getTag()->id, $response->getTag()->tag));
        }
    }

    /**
     * @param string $url
     * @param string $token
     * @param int    $index
     * @param array  $transaction
     *
     * @return TransactionGroup|null
     */
    private function sendTransaction(string $url, string $token, int $index, array $transaction): ?TransactionGroup
    {
        Log::debug(sprintf('Now in %s', __METHOD__));
        Log::debug('Will send to Firefly III: ', $transaction);
        $request = new PostTransactionRequest($url, $token);

        $request->setVerify(config('importer.connection.verify'));
        $request->setTimeOut(config('importer.connection.timeout'));
        $request->setBody($transaction);
        try {
            /** @var PostTransactionResponse $response */
            $response = $request->post();
        } catch (ApiHttpException $e) {
            app('log')->error($e->getMessage());
            $this->addError($index, $e->getMessage());

            return null;
        }
        if ($response instanceof ValidationErrorResponse) {
            /** ValidationErrorResponse $error */
            foreach ($response->errors->getMessages() as $key => $errors) {
                foreach ($errors as $error) {
                    // +1 so the line numbers match.
                    $this->addError($index + 1, $error);
                    app('log')->error(sprintf('Could not create transaction: %s', $error), $transaction);
                }
            }

            return null;
        }
        /** @var TransactionGroup|null $group */
        $group = $response->getTransactionGroup();
        if (null === $group) {
            $this->addError($index + 1, 'Group is unexpectedly NULL.');

            return null;
        }
        $groupId  = $group->id;
        $groupUrl = (string) sprintf('%s/transactions/show/%d', $this->rootURL, $groupId);

        /** @var Transaction $tr */
        foreach ($group->transactions as $tr) {
            $this->addMessage(
                $index + 1,
                sprintf(
                    'Created transaction #%d: <a href="%s">%s</a> (%s %s)', $groupId, $groupUrl, $tr->description, $tr->currencyCode,
                    round((float) $tr->amount, 2)
                )
            );
        }
        Log::debug(sprintf('Done with %s', __METHOD__));
        return $group;
    }

    /**
     * @param TransactionGroup $group
     */
    private function addTagToGroup(TransactionGroup $group): void
    {
        if (false === $this->addTag) {
            Log::debug('Will not add import tag.');

            return;
        }

        $groupId = (int) $group->id;
        Log::debug(sprintf('Going to add import tag to transaction group #%d', $groupId));
        $body = [
            'transactions' => [],
        ];
        /** @var Transaction $transaction */
        foreach ($group->transactions as $transaction) {
            /** @var array $currentTags */
            $currentTags   = $transaction->tags;
            $currentTags[] = $this->tag;

            $body['transactions'][] = [
                'transaction_journal_id' => $transaction->id,
                'tags'                   => $currentTags,
            ];
        }
        $url     = (string) config('importer.url');
        $token   = (string) config('importer.access_token');
        $request = new PutTransactionRequest($url, $token, $groupId);
        $request->setVerify(config('importer.connection.verify'));
        $request->setTimeOut(config('importer.connection.timeout'));
        $request->setBody($body);
        $request->put();

    }

    /**
     * @param Configuration $configuration
     */
    public function setConfiguration(Configuration $configuration): void
    {
        $this->addTag        = true;
        $this->configuration = $configuration;
        $this->addTag        = $configuration->isAddImportTag();
    }
}
