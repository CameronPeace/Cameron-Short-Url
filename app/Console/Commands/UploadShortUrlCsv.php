<?php

namespace App\Console\Commands;

use App\Jobs\ProcessShortUrlCsv;
use App\Traits\HandlesCsv;
use Illuminate\Console\Command;

class UploadShortUrlCsv extends Command
{
    use HandlesCsv;

    protected $signature = 'upload:redirects {file}';

    protected $description = 'Add a csv file of redirects for short url creation.';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!$this->fileExists($filePath)) {
            $this->error("File does not exist at path: " . $filePath);
            return;
        }

        if (!$this->isCsv($filePath)) {
            $this->error('File is not a csv.');
            return;
        }

        if (!$this->isValidSize($filePath)) {
            $this->error("File exceeds the maximum allowed size: " . $filePath);
            return;
        }

        ProcessShortUrlCsv::dispatch($filePath);

        $this->info('Your upload has been queued!');
    }
}
