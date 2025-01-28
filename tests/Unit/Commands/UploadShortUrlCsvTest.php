<?php

namespace Tests\Unit\Commands;

use App\Jobs\ProcessShortUrlCsv;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class UploadShortUrlCsvTest extends TestCase
{
    /**
     * Test that the upload:redirects command cannot be executed without the file argument.
     */
    public function testFileIsRequired()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "file")');
        $this->artisan('upload:redirects');
    }

    /**
     * Test that the job is queued to process the csv file.
     */
    public function testJobQueuesShortUrlProcess()
    {
        Queue::fake();

        $data = [
            ['url'],
            ['url' => 'example.com'],
            ['url' => 'test.com'],
            ['url' => 'https://research.com'],
            ['url' => 'www.book.com'],
        ];

        $filePath = 'storage/redirects.csv';
        $file = fopen($filePath, 'w');
        foreach ($data as $line) {
            fputcsv($file, $line, ',');
        }
        fclose($file);

        $this->artisan('upload:redirects ' . $filePath)->expectsOutput('Your upload has been queued!');
        Queue::assertPushed(ProcessShortUrlCsv::class);
        unlink($filePath);
    }
}
