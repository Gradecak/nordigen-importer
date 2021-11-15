<?php
declare(strict_types=1);

namespace App\Console;

use App\Exceptions\ImportException;
use App\Mail\ImportFinished;
use Illuminate\Support\Facades\Mail;
use JsonException;
use Log;

/**
 * Trait AutoImports
 */
trait AutoImports
{
    /**
     * @return array
     */
    protected function getFiles(): array
    {
        $ignore = ['.', '..'];

        if (null === $this->directory || '' === $this->directory) {
            $this->error(sprintf('Directory "%s" is empty or invalid.', $this->directory));

            return [];
        }
        $array = scandir($this->directory);
        if (!is_array($array)) {
            $this->error(sprintf('Directory "%s" is empty or invalid.', $this->directory));

            return [];
        }
        $files  = array_diff($array, $ignore);
        $return = [];
        foreach ($files as $file) {
            if ('json' === $this->getExtension($file)) {
                $return[] = $file;
            }
        }

        return $return;
    }


    /**
     * @param string $file
     *
     * @return string
     */
    private function getExtension(string $file): string
    {
        $parts = explode('.', $file);
        if (1 === count($parts)) {
            return '';
        }

        return strtolower($parts[count($parts) - 1]);
    }

    /**
     * @param array $files
     *
     * @throws ImportException
     */
    protected function importFiles(array $files): void
    {
        /** @var string $file */
        foreach ($files as $file) {
            $this->importFile($file);
        }
    }

    /**
     * @param string $file
     *
     * @throws ImportException
     */
    private function importFile(string $file): void
    {
        $jsonFile = sprintf('%s/%s.json', $this->directory, substr($file, 0, -4));

        // do JSON check
        $jsonResult = $this->verifyJSON($jsonFile);
        if (false === $jsonResult) {
            $message = sprintf('The importer can\'t import %s: could not decode the JSON in config file %s.', $csvFile, $jsonFile);
            $this->error($message);

            return;
        }
        try {
            $configuration = json_decode(file_get_contents($jsonFile), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            Log::error($e->getMessage());
            throw new ImportException(sprintf('Bad JSON in configuration file: %s', $e->getMessage()));
        }
        $this->line(sprintf('Going to import using configuration %s.', $jsonFile));

        // TODO start
        if (null !== $this->argument('downloadIdentifier')) {
            $downloadIdentifier = $this->argument('downloadIdentifier');
            $this->line(sprintf('You have submitted an existing download: "%s"', $downloadIdentifier));
            $this->downloadIdentifier = $downloadIdentifier;
        }
        if (null === $this->argument('downloadIdentifier')) {
            $result = $this->startDownload($configuration);
            if (0 === $result) {
                $this->line('Download from Nordigen complete.');
            }
            if (0 !== $result) {
                $this->warn('Download from Nordigen resulted in errors.');

                return;
            }
        }

        $secondResult = $this->startSync($configuration);
        if (0 === $secondResult) {
            $this->line('Sync to Firefly III complete.');
        }
        if (0 !== $secondResult) {
            $this->warn('Sync to Firefly III resulted in errors.');
        }
        // TODO end

    }

    /**
     * @param string $file
     *
     * @throws ImportException
     */
    private function importUpload(string $csvFile, string $jsonFile): void
    {
        // do JSON check
        $jsonResult = $this->verifyJSON($jsonFile);
        if (false === $jsonResult) {
            $message = sprintf('The importer can\'t import %s: could not decode the JSON in config file %s.', $csvFile, $jsonFile);
            $this->error($message);

            return;
        }
        try {
            $configuration = json_decode(file_get_contents($jsonFile), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            Log::error($e->getMessage());
            throw new ImportException(sprintf('Bad JSON in configuration file: %s', $e->getMessage()));
        }
        $this->line(sprintf('Going to import from file %s using configuration %s.', $csvFile, $jsonFile));
        // create importer
        $csv    = file_get_contents($csvFile);
        $result = $this->startImport($csv, $configuration);

        if (0 === $result) {
            $this->line('Import complete.');
        }
        if (0 !== $result) {
            $this->warn('The import finished with errors.');
        }

        $this->line(sprintf('Done importing from file %s using configuration %s.', $csvFile, $jsonFile));

        // send mail:
        $log
            = [
            'messages' => $this->messages,
            'warnings' => $this->warnings,
            'errors'   => $this->errors,
        ];

        $send = config('mail.enable_mail_report');
        Log::debug('Log log', $log);
        if (true === $send) {
            Log::debug('SEND MAIL');
            Mail::to(config('mail.destination'))->send(new ImportFinished($log));
        }
    }
}
