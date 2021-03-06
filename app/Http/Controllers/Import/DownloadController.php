<?php
/*
 * DownloadController.php
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

namespace App\Http\Controllers\Import;


use App\Http\Controllers\Controller;
use App\Services\Configuration\Configuration;
use App\Services\Nordigen\Download\JobStatus\JobStatus;
use App\Services\Nordigen\Download\JobStatus\JobStatusManager;
use App\Services\Nordigen\Download\RoutineManager;
use App\Services\Session\Constants;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Log;

/**
 * Class DownloadController
 */
class DownloadController extends Controller
{
    /**
     * DownloadController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        app('view')->share('pageTitle', 'Download transactions from Nordigen');
    }

    /**
     */
    public function index()
    {
        app('log')->debug(sprintf('Now at %s', __METHOD__));
        $mainTitle = 'Downloading transactions...';
        $subTitle  = 'Connecting to Nordigen and downloading your data...';

        // job ID may be in session:
        $downloadIdentifier = session()->get(Constants::DOWNLOAD_JOB_IDENTIFIER);
        if (null === $downloadIdentifier) {
            Log::debug('No download identifier in session.');
            // create a new download job:
            $routine            = new RoutineManager;
            $downloadIdentifier = $routine->getDownloadIdentifier();
        }
        Log::debug(sprintf('Download identifier is %s.', $downloadIdentifier));

        // call thing:
        JobStatusManager::startOrFindJob($downloadIdentifier);

        app('log')->debug(sprintf('Download routine manager identifier is "%s"', $downloadIdentifier));

        // store identifier in session so the status can get it.
        session()->put(Constants::DOWNLOAD_JOB_IDENTIFIER, $downloadIdentifier);
        app('log')->debug(sprintf('Stored "%s" under "%s"', $downloadIdentifier, Constants::DOWNLOAD_JOB_IDENTIFIER));

        return view('import.004-download.index', compact('mainTitle', 'subTitle', 'downloadIdentifier'));
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function start(Request $request): JsonResponse
    {
        app('log')->debug(sprintf('Now at %s', __METHOD__));
        $downloadIdentifier = $request->get('downloadIdentifier');
        $routine            = new RoutineManager($downloadIdentifier);
        JobStatusManager::startOrFindJob($downloadIdentifier);

        // store identifier in session so the status can get it.
        session()->put(Constants::DOWNLOAD_JOB_IDENTIFIER, $downloadIdentifier);

        $downloadJobStatus = JobStatusManager::startOrFindJob($downloadIdentifier);
        if (JobStatus::JOB_DONE === $downloadJobStatus->status) {
            app('log')->debug('Job already done!');

            return response()->json($downloadJobStatus->toArray());
        }
        JobStatusManager::setJobStatus(JobStatus::JOB_RUNNING);

        $config = session()->get(Constants::CONFIGURATION) ?? [];
        $routine->setConfiguration(Configuration::fromArray($config));
        $routine->start();

        // set done:
        JobStatusManager::setJobStatus(JobStatus::JOB_DONE);

        return response()->json($downloadJobStatus->toArray());
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function status(Request $request): JsonResponse
    {
        $downloadIdentifier = $request->get('downloadIdentifier');
        if (null === $downloadIdentifier) {
            app('log')->warning('Download Identifier is NULL.');
            // no status is known yet because no identifier is in the session.
            // As a fallback, return empty status
            $fakeStatus = new JobStatus();

            return response()->json($fakeStatus->toArray());
        }
        $importJobStatus = JobStatusManager::startOrFindJob($downloadIdentifier);

        return response()->json($importJobStatus->toArray());
    }

}
