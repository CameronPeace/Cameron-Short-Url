<?php

namespace App\Jobs;

use App\Services\ShortUrlService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessShortUrlCsv implements ShouldQueue
{
    use Queueable;

    private $shortUrlService;

    private $filePath;

    const LONG_URL_COLUMN_HEADER = 'url';

    const ACCEPTED_HEADERS = [
        self::LONG_URL_COLUMN_HEADER
    ];

    /**
     * Create a new job instance.
     */
    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        $this->shortUrlService = new ShortUrlService();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!file_exists($this->filePath)) {
            \Log::error('File ' . $this->filePath . ' does not exist');
            return;
        }

        if (pathinfo($this->filePath, PATHINFO_EXTENSION) !== 'csv') {
            \Log::error('File is not a csv.');
            return;
        }

        // 500 MB in bytes
        $maxFileSize = 500 * 1024 * 1024;

        if (!file_exists($this->filePath)) {
            throw new RuntimeException("File does not exist: " . $this->filePath);
        }

        $fileSize = filesize($this->filePath);

        var_dump($fileSize);
        if ($fileSize > $maxFileSize) {
            throw new RuntimeException("File size exceeds the maximum allowed size of 500MB: " . $this->filePath);
        }

        $file = fopen($this->filePath, "r");

        $headers = fgetcsv($file);

        $columnHeaders = $this->prepareHeaders($headers);

        $headersMapping = $this->mapHeaders($columnHeaders);

        if (empty($headersMapping)) {
            \Log::error('Columns invalid');
            return;
        }

        $this->processFile($headersMapping, $file);
    }

    private function prepareHeaders(array $headers)
    {
        return array_map(function ($header) {
            return trim(strtolower($header));
        }, $headers);
    }

    private function mapHeaders(array $headers)
    {
        $mapped = [];

        for ($i = 0; $i <= COUNT($headers); $i++) {

            if (in_array($headers[$i], self::ACCEPTED_HEADERS)) {
                $mapped[$headers[$i]] = $i;
            }
        }

        return $mapped;
    }

    private function processFile($headersMapping, $file)
    {

        $row = [];

        while (($data = fgetcsv($file)) !== FALSE) {
            echo $data[0] . "\n";

            $row = array_map(function ($mapping) use ($data) {
                return $data[$mapping];
            }, $headersMapping);

            var_dump(json_encode($row));

            $this->processRow($row);
        }

        fclose($file);
    }

    public function processRow(array $row)
    {
        $saved = $this->shortUrlService->createShortUrl($row[self::LONG_URL_COLUMN_HEADER]);

        if (!$saved) {
            // TODO do some logging.
            \Log::info('Failed to create short url for ' . $row[self::LONG_URL_COLUMN_HEADER]);
        }
    }
}
