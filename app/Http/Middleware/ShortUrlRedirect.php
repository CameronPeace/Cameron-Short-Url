<?php

namespace App\Http\Middleware;

use App\Jobs\SaveShortUrlClick;
use App\Models\Repositories\ShortUrlRepository;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShortUrlRedirect
{
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
            $code = $request->segment(2);
            \Log::info($code);
            $shortUrlRepository = new ShortUrlRepository();
            $shortUrl = $shortUrlRepository->get($code);

            \Log::info($shortUrl);
            if (!empty($shortUrl) && !empty($shortUrl['redirect'])) {

                $clickData = [
                    'id' => $shortUrl['id'],
                    'occurred_at' => date('Y-m-d H:i:s'),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ];

                SaveShortUrlClick::dispatchSync($clickData);
                return redirect($shortUrl['redirect']);
            }
        }

        return $next($request);
    }
}
