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

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        $this->shortUrlService = new ShortUrlService();
    }

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

        // 50 MB
        $maxFileSize = 50 * 1024 * 1024;

        if (!file_exists($this->filePath)) {
            \Log::error("File does not exist: " . $this->filePath);
            return;
        }

        $fileSize = filesize($this->filePath);

        var_dump($fileSize);
        if ($fileSize > $maxFileSize) {
            \Log::error("File size exceeds the maximum allowed size of 500MB: " . $this->filePath);
            return;
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

    /**
     * Standardize headers for matching.
     *
     * @param array $headers
     *
     * @return array
     */
    private function prepareHeaders(array $headers)
    {
        return array_map(function ($header) {
            return trim(strtolower($header));
        }, $headers);
    }

    /**
     * Map the file headers to our allowed headers and return the index mapping for those allowed.
     *
     * @param array $headers
     *
     * @return array
     */
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

    /**
     * Process the csv.
     *
     * @param array $headersMapping
     * @param string $file
     *
     * @return void
     */
    private function processFile(array $headersMapping, string $file)
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

    /**
     * Process a single csv row.
     *
     * @param array $row
     *
     * @return void
     */
    public function processRow(array $row)
    {
        $saved = $this->shortUrlService->createShortUrl($row[self::LONG_URL_COLUMN_HEADER]);

        if (!$saved) {
            // TODO do some logging.
            \Log::info('Failed to create short url for ' . $row[self::LONG_URL_COLUMN_HEADER]);
        }
    }
}
