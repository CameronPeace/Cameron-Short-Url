<?php

namespace App\Jobs;

use App\Services\ShortUrlService;
use App\Traits\HandlesCsv;
use App\Traits\LoggableJob;
use App\Traits\ShortUrl;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessShortUrlCsv implements ShouldQueue
{
    use Queueable, ShortUrl, LoggableJob, HandlesCsv;

    public $tries = 1;

    const LONG_URL_COLUMN_HEADER = 'url';
    const DOMAIN_COLUMN_HEADER = 'domain';

    const ACCEPTED_HEADERS = [
        self::LONG_URL_COLUMN_HEADER,
        self::DOMAIN_COLUMN_HEADER
    ];

    /** 
     * @var ShortUrlService
     */
    private $shortUrlService;

    /**
     * The csv filePath provided to the job.
     *
     * @var string
     */
    private $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        $this->shortUrlService = new ShortUrlService();
    }

    public function handle(): void
    {
        if (!$this->fileExists($this->filePath)) {
            \Log::error('File ' . $this->filePath . ' does not exist');
            $this->log(get_class($this), 'FILE_REJECTED_404', ['file' => $this->filePath, 'message' => 'File does not exist.']);
            return;
        }

        if (!$this->isCsv($this->filePath)) {
            \Log::error('File is not a csv: ' . $this->filePath);
            $this->log(get_class($this), 'FILE_REJECTED_TYPE', ['file' => $this->filePath, 'message' => 'File is not a csv.']);
            return;
        }

        if (!$this->isValidSize($this->filePath)) {
            $message = sprintf('File %s exceeds the maximum allowed size of %s', $this->filePath, $this->getMaxFileSize());
            \Log::error($message);
            $this->log(get_class($this), 'FILE_REJECTED_SIZE', ['file' => $this->filePath, 'message' => $message]);
            return;
        }

        $headersMapping = $this->prepareHeaderMappings($this->filePath);

        if (empty($headersMapping)) {
            \Log::error('Invalid columns for short_url file ' . $this->filePath);
            $this->log(get_class($this), 'FILE_REJECTED', ['file' => $this->filePath, 'message' => 'No processable columns.']);
            return;
        }

        $this->processFile($headersMapping, $this->filePath);

        $this->log(get_class($this), 'UPLOAD_COMPLETE', ['file' => $this->filePath]);
    }

    /**
     * Standardize headers for matching.
     *
     * @param array $headers
     *
     * @return array
     */
    public function prepareHeaders(array $headers)
    {
        return array_map(function ($header) {
            return trim(strtolower($header));
        }, $headers);
    }

    /**
     * Create a mapping between the allowed and the file headers.
     *
     * @param string $file
     *
     * @return array
     */
    public function prepareHeaderMappings($filePath)
    {
        $file = fopen($filePath, "r");
        $headers = fgetcsv($file);
        fclose($file);

        $columnHeaders = $this->prepareHeaders($headers);
        return $this->mapHeaders($columnHeaders);
    }

    /**
     * Map the file headers to our allowed headers and return the index mapping for those allowed.
     *
     * @param array $headers
     *
     * @return array
     */
    public function mapHeaders(array $headers)
    {
        $mapped = [];

        for ($i = 0; $i < COUNT($headers); $i++) {
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
     * @param string $filePath
     * 
     * @return void
     */
    public function processFile(array $headersMapping, string $filePath)
    {
        $row = [];
        $file = fopen($filePath, "r");
        //skipping headers
        fgetcsv($file);

        while (($data = fgetcsv($file)) !== FALSE) {
            $row = array_map(function ($mapping) use ($data) {
                return $data[$mapping];
            }, $headersMapping);

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
        $domain = in_array($row['domain'] ?? null, self::DOMAINS) ?  $row['domain'] : null;

        $url = $this->sanitizeUrl($row[self::LONG_URL_COLUMN_HEADER]);

        if (!empty($url)) {
            $saved = $this->shortUrlService->createShortUrl($url, $domain);

            if (!$saved) {
                \Log::info('Failed to create short url for ' . $row[self::LONG_URL_COLUMN_HEADER]);
            }
        } else {
            \Log::info('Failed to sanitize redirect ' . $row[self::LONG_URL_COLUMN_HEADER]);
        }
    }
}
