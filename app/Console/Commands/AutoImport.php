<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\AutoImports;
use App\Console\HaveAccess;
use App\Console\StartImport;
use App\Console\VerifyJSON;
use App\Exceptions\ImportException;
use Illuminate\Console\Command;
use Log;

/**
 * Class AutoImport
 */
class AutoImport extends Command
{
    use HaveAccess, VerifyJSON, StartImport, AutoImports;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will automatically import from the given directory and use the JSON file(s) found.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'importer:auto-import {directory : The directory from which to import automatically.}';
    /** @var string */
    private $directory = './';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $access = $this->haveAccess();
        if (false === $access) {
            $this->error('Could not connect to your local Firefly III instance.');

            return 1;
        }

        $argument        = (string) ($this->argument('directory') ?? './');
        $this->directory = realpath($argument);
        $this->line(sprintf('Going to automatically import everything found in %s (%s)', $this->directory, $argument));

        $files = $this->getFiles();
        if (0 === count($files)) {
            $this->info(sprintf('There are no files in directory %s', $this->directory));
            $this->info('To learn more about this process, read the docs:');
            $this->info('https://docs.firefly-iii.org/');

            return 1;
        }
        $this->line(sprintf('Found %d JSON file sets in %s', count($files), $this->directory));
        try {
            $this->importFiles($files);
        } catch (ImportException $e) {
            Log::error($e->getMessage());
            $this->error(sprintf('Import exception (see the logs): %s', $e->getMessage()));
        }

        return 0;
    }

}
