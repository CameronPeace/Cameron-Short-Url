<?php

namespace App\Jobs;

use App\Models\ShortUrlClick;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SaveShortUrlClick implements ShouldQueue
{
    use Queueable;

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
        \Log::info(json_encode($this->clickData));

        if (empty($this->clickData['id'])) {
            \Log::error('No short url ID to associate.');
            return;
        }

        $shortUrlClickModel = new ShortUrlClick();

        try {
            $saved = $shortUrlClickModel->create([
                'short_url_id' => $this->clickData['id'],
                'occurred_at' => $this->clickData['occurred_at'] ??  date('Y-m-d H:i:s'),
                'ip_address' => $this->clickData['ip_address'] ?? null, 
                'user_agent' => $this->clickData['user_agent'] ?? null
            ]);
    
            if (!$saved) {
                // TODO add logs
                \Log::error('Unable to insert click data');
            }
            
        } catch (\Exception $e) {
            \Log::error('Exception Thrown while processing click: ' . $e->getMessage());
        } 
    }
}
