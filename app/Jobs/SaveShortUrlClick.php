<?php

namespace App\Jobs;

use App\Models\ShortUrl;
use App\Models\ShortUrlClick;
use App\Services\ShortUrlService;
use App\Traits\LoggableJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SaveShortUrlClick implements ShouldQueue
{
    use Queueable, LoggableJob;

    private $clickRepository;

    private $clickData;

    /**
     * Create a new job instance.
     */
    public function __construct($urlClickData)
    {
        $this->clickData = $urlClickData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        if (empty($this->clickData['short_url_id']) && empty($this->clickData['code'])) {
            \Log::error('No short url ID to associate.');
            return;
        }

        if (empty($this->clickData['short_url_id'])) {
            $shortUrlService = new ShortUrlService();
            $record = $shortUrlService->getShortUrl($this->clickData['code'], $this->clickData['domain']);
            if (empty($record)) {
                $this->log(get_class($this), 'CLICK_NO_URL', array_merge($this->clickData, ['message' => 'No short url could be found for this click']));
                return;
            }

            $this->clickData['short_url_id'] = $record['id'];
        }

        $shortUrlClickModel = new ShortUrlClick();

        try {
            $saved = $shortUrlClickModel->create([
                'short_url_id' => $this->clickData['short_url_id'],
                'occurred_at' => $this->clickData['occurred_at'] ??  date('Y-m-d H:i:s'),
                'ip_address' => $this->clickData['ip_address'] ?? null,
                'user_agent' => $this->clickData['user_agent'] ?? null
            ]);

            if (empty($saved['id'])) {
                \Log::error('Unable to insert click data');
                $this->log(get_class($this), 'CLICK_FAILED', $this->clickData);
            }
        } catch (\Exception $e) {
            \Log::error('Exception Thrown while processing click: ' . $e->getMessage());
            $this->log(get_class($this), 'CLICK_FAILED', array_merge($this->clickData, ['error' => $e->getMessage()]));
        }
    }
}
