<?php

namespace App\Http\Middleware;

use App\Jobs\SaveShortUrlClick;
use App\Services\ShortUrlService;
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
        // we are using localhost/s/ to act as our domain.
        if ($request->is('s/*')) {

            $host = $request->getHost();
            $code = $request->segment(2);

            $clickData = [
                'domain' => $host,
                'code' => $code,
                'occurred_at' => date('Y-m-d H:i:s'),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ];

            $cacheKey = $host . '_' . $code;
            $cachedRedirect = \Cache::get($cacheKey);

            // use our cache value if we have it.
            if (!empty($cachedRedirect)) {
                SaveShortUrlClick::dispatch($clickData);
                return redirect($cachedRedirect);
            }

            $shortUrlService = new ShortUrlService();
            $record = $shortUrlService->getShortUrl($code, $host);

            if (!empty($record) && !empty($record['redirect'])) {

                \Cache::put($cacheKey, $record['redirect'], 600);

                $clickData['short_url_id'] = $record['id'];

                SaveShortUrlClick::dispatch($clickData);

                return redirect($record['redirect']);
            }
        }

        return $next($request);
    }
}
