<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SaveShortUrlClick;
use App\Models\ShortUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaveShortUrlClickTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test that when called the job creates a new short_url_click.
     */
    public function testSaveShortUrlClickCreatesClickRecords()
    {
        $shortUrl = new ShortUrl();
        $urlRecord = $shortUrl->create(['redirect' => 'https://www.google.com', 'code' => 'xsauf', 'domain' => 'mod.io']);

        $clickData = [
            'short_url_id' => $urlRecord['id'],
            'occurred_at' => date('Y-m-d H:i:s'),
            'ip_address' => '172.26.0.1',
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:135.0) Gecko/20100101 Firefox/135.0'
        ];

        $job = new SaveShortUrlClick($clickData);
        $job->handle();

        $this->assertDatabaseHas('short_url_click', $clickData);
    }
}
