<?php

namespace App\Http\Middleware;

use App\Jobs\SaveShortUrlClick;
use App\Models\Repositories\ShortUrlRepository;
use App\Traits\ShortUrl;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShortUrlRedirect
{
    use ShortUrl;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        \Log::info('Short Url redirect');

        // we are using localhost/s/ to act as our domain.
        if ($request->is('s/*')) {
            $host = $request->getHost();
            $code = $request->segment(2);

            $cacheKey = $host . '_' . $code;
            $cachedRedirect = \Cache::get($cacheKey);

            if (!empty($cachedRedirect)) {
                return redirect($cachedRedirect);
            }

            $shortUrlRepository = new ShortUrlRepository();
            $shortUrl = $shortUrlRepository->get($code);

            if (!empty($shortUrl) && !empty($shortUrl['redirect'])) {

                \Cache::put($cacheKey, $shortUrl['redirect'], 600);

                $url = $this->sanitizeUrl($shortUrl['redirect']);

                if (!empty($url)) {

                    $clickData = [
                        'id' => $shortUrl['id'],
                        'occurred_at' => date('Y-m-d H:i:s'),
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent()
                    ];

                    SaveShortUrlClick::dispatch($clickData);
                    return redirect($this->sanitizeUrl($url));
                }
            }
        }

        return $next($request);
    }
}
