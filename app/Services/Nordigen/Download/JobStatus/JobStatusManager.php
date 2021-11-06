<?php
/*
 * JobStatusManager.php
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

namespace App\Services\Nordigen\Download\JobStatus;

use App\Exceptions\ImporterErrorException;
use App\Services\Session\Constants;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Storage;
use JsonException;

/**
 * Class JobStatusManager.
 */
class JobStatusManager
{
    /**
     * @param string $downloadIdentifier
     * @param int    $index
     * @param string $error
     * @throws ImporterErrorException
     */
    public static function addError(string $downloadIdentifier, int $index, string $error): void
    {
        $disk = Storage::disk('jobs');
        try {
            if ($disk->exists($downloadIdentifier)) {
                $status                   = JobStatus::fromArray(json_decode($disk->get($downloadIdentifier), true, 512, JSON_THROW_ON_ERROR));
                $status->errors[$index]   = $status->errors[$index] ?? [];
                $status->errors[$index][] = $error;
                self::storeJobStatus($downloadIdentifier, $status);
            }
        } catch (FileNotFoundException $e) {
            app('log')->error($e->getMessage());
            app('log')->error($e->getTraceAsString());
        } catch (JsonException $e) {
            throw new ImporterErrorException($e->getMessage(), 0, $e);
        }


    }

    /**
     * @param string    $downloadIdentifier
     * @param JobStatus $status
     * @throws ImporterErrorException
     */
    private static function storeJobStatus(string $downloadIdentifier, JobStatus $status): void
    {
        app('log')->debug(sprintf('Now in storeJobStatus(%s): %s', $downloadIdentifier, $status->status));
        $disk = Storage::disk('jobs');
        try {
            $disk->put($downloadIdentifier, json_encode($status->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
        } catch (JsonException $e) {
            throw new ImporterErrorException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @param string $downloadIdentifier
     * @param int    $index
     * @param string $message
     * @throws ImporterErrorException
     */
    public static function addMessage(string $downloadIdentifier, int $index, string $message): void
    {
        $disk = Storage::disk('jobs');
        try {
            if ($disk->exists($downloadIdentifier)) {
                $status                     = JobStatus::fromArray(json_decode($disk->get($downloadIdentifier), true, 512, JSON_THROW_ON_ERROR));
                $status->messages[$index]   = $status->messages[$index] ?? [];
                $status->messages[$index][] = $message;
                self::storeJobStatus($downloadIdentifier, $status);
            }
        } catch (FileNotFoundException $e) {
            app('log')->error($e->getMessage());
            app('log')->error($e->getTraceAsString());
        }
    }

    /**
     * @param string $downloadIdentifier
     * @param int    $index
     * @param string $warning
     * @throws ImporterErrorException
     */
    public static function addWarning(string $downloadIdentifier, int $index, string $warning): void
    {
        $disk = Storage::disk('jobs');
        try {
            if ($disk->exists($downloadIdentifier)) {
                $status                     = JobStatus::fromArray(json_decode($disk->get($downloadIdentifier), true, 512, JSON_THROW_ON_ERROR));
                $status->warnings[$index]   = $status->warnings[$index] ?? [];
                $status->warnings[$index][] = $warning;
                self::storeJobStatus($downloadIdentifier, $status);
            }
        } catch (FileNotFoundException $e) {
            app('log')->error($e->getMessage());
            app('log')->error($e->getTraceAsString());
        }
    }

    /**
     * @param string $status
     *
     * @return JobStatus
     * @throws ImporterErrorException
     */
    public static function setJobStatus(string $status): JobStatus
    {
        $downloadIdentifier = session()->get(Constants::DOWNLOAD_JOB_IDENTIFIER);
        app('log')->debug(sprintf('Now in download setJobStatus(%s) for job %s', $status, $downloadIdentifier));

        $jobStatus         = self::startOrFindJob($downloadIdentifier);
        $jobStatus->status = $status;

        self::storeJobStatus($downloadIdentifier, $jobStatus);

        return $jobStatus;
    }

    /**
     * @param string $downloadIdentifier
     *
     * @return JobStatus
     * @throws ImporterErrorException
     */
    public static function startOrFindJob(string $downloadIdentifier): JobStatus
    {
        $disk = Storage::disk('jobs');
        try {
            if ($disk->exists($downloadIdentifier)) {
                $array  = json_decode($disk->get($downloadIdentifier), true, 512, JSON_THROW_ON_ERROR);
                $status = JobStatus::fromArray($array);

                return $status;
            }
        } catch (FileNotFoundException $e) {
            app('log')->error('Could not find download job file, write a new one.');
            app('log')->error($e->getMessage());
        } catch (JsonException $e) {
            throw new ImporterErrorException($e->getMessage(), 0, $e);
        }
        app('log')->debug('Download job file does not exist or error, create a new one.');
        $status = new JobStatus;
        try {
            $disk->put($downloadIdentifier, json_encode($status->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
        } catch (JsonException $e) {
            throw new ImporterErrorException($e->getMessage(), 0, $e);
        }

        app('log')->debug('Return download job status.', $status->toArray());

        return $status;
    }
}
