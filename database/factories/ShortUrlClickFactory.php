<?php

namespace Database\Factories;

use App\Models\ShortUrl;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShortUrlClick>
 */
class ShortUrlClickFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $shortUrls = ShortUrl::pluck('id')->toArray();
        
        $ipOptions = ['75.25.205.131', '46.3.135.47', '238.192.120.206', '152.36.190.54'];
        $userAgentOptions = ['Mozilla/5.0 (compatible; MSIE 7.0; Windows; Windows NT 6.3; Trident/4.0)',
        'Mozilla/5.0 (compatible; MSIE 7.0; Windows; Windows NT 6.3; Trident/4.0)',
        'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 9_3_9) AppleWebKit/535.3 (KHTML, like Gecko) Chrome/50.0.1108.389 Safari/602'];
        
        $ShortUrlId = fake()->randomElement($shortUrls);

        return [
            'ip_address' => fake()->randomElement($ipOptions),
            'user_agent' => fake()->randomElement($userAgentOptions),
            'short_url_id' => $ShortUrlId,
            'occurred_at' => fake()->dateTimeBetween('-1 days', 'now')
        ];
    }
}
