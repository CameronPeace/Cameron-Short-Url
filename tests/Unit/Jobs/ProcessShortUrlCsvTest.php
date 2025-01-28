<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessShortUrlCsv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessShortUrlCsvTest extends TestCase
{

    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test prepareHeaders sanitizes our headers.
     */
    public function testPrepareHeaders()
    {
        $job = new ProcessShortUrlCsv('notNeeded.csv');
        $headers = ['  URL  ', 'Query_Params', 'PARAM_ID'];
        $expected = ['url', 'query_params', 'param_id'];
        $this->assertEquals($expected, $job->prepareHeaders($headers));
    }

    /**
     * Test mapHeaders only returns header mappings from accepted columns.
     */
    public function testMapHeaders()
    {
        $job = new ProcessShortUrlCsv('notNeeded.csv');
        $headers =  ['query_params', 'match_id', 'url', 'tag'];

        $mapped = $job->mapHeaders($headers);
        $urlIndex = array_search('url', $headers);

        $this->assertArrayNotHasKey('tag', $mapped);
        $this->assertArrayNotHasKey('match_id', $mapped);
        $this->assertEquals($urlIndex, $mapped['url']);
    }

    /**
     * Test prepareHeaderMappings results in a mapping of our column headers to ur allowed headers
     *
     * @return void
     */
    public function testPrepareHeaderMappings()
    {
        $data = [
            [' URL ', 'PARAM_ID', 'domain'],
            ['url' => 'https://example.com', 'param_id' => '12', 'domain' => 'bot.io'],
            ['url' => 'https://laravel.com', 'param_id' => '12', 'domain' => 'bot.io', 'param_id' => '12'],
            ['url' => 'https://www.google.com', 'param_id' => '12', 'domain' => 'bot.io', 'param_id' => '12'],
        ];

        $filePath = 'storage/redirects.csv';
        $file = fopen($filePath, 'w');
        foreach ($data as $line) {
            fputcsv($file, $line, ',');
        }
        fclose($file);

        $job = new ProcessShortUrlCsv($filePath);
        $expected = ['url' => 0, 'domain' => 2];
        $mapping = $job->prepareHeaderMappings($filePath);
        $this->assertEqualsCanonicalizing($expected, $mapping);

        unlink($filePath);
    }

    /**
     * Test processRow results in a new short_url record.
     */
    public function testProcessRowCreatesNewRecords()
    {
        $row = [
            'url' => 'https://www.internet.com',
            'domain' => 'mod.io'
        ];

        $data = [
            'redirect' => $row['url'],
            'domain' =>  $row['domain']
        ];

        $job = new ProcessShortUrlCsv('notNeeded.csv');
        $job->processRow($row);

        $this->assertDatabaseHas('short_url', $data);
    }

    /**
     * Test that the processFile function results in new records.
     */
    public function testProcessFileCreatesNewRecords()
    {
        $data = [
            ['url'],
            ['url' => 'https://example.com'],
            ['url' => 'https://laravel.com'],
            ['url' => 'https://www.google.com'],
        ];

        $mapping = [
            'url' => 0
        ];

        $filePath = 'storage/redirects.csv';
        $file = fopen($filePath, 'w');
        foreach ($data as $line) {
            fputcsv($file, $line, ',');
        }
        fclose($file);

        $job = new ProcessShortUrlCsv($filePath);
        $job->processFile($mapping, $filePath);

        $this->assertDatabaseHas('short_url', ['redirect' => 'https://www.google.com']);
        $this->assertDatabaseHas('short_url', ['redirect' => 'https://laravel.com']);
        $this->assertDatabaseHas('short_url', ['redirect' => 'https://example.com']);

        unlink($filePath);
    }

    /**
     * Test that the job does not attempt to process a file too large.
     */
    public function testMaxFileSize()
    {
        $data = [
            ['url'],
            ['url' => 'https://example.com'],
            ['url' => 'https://laravel.com'],
            ['url' => 'https://www.google.com'],
        ];

        $filePath = 'storage/redirects.csv';
        $file = fopen($filePath, 'w');
        foreach ($data as $line) {
            fputcsv($file, $line, ',');
        }
        fclose($file);

        $job = new ProcessShortUrlCsv($filePath);

        $job->setMaxFileSize(1);
        $job->handle();

        unlink($filePath);

        $this->assertDatabaseHas('job_log', ['type' => 'FILE_REJECTED_SIZE', 'job' => 'App\Jobs\ProcessShortUrlCsv']);
    }
}
