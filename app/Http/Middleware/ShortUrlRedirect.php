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

        // we are using localhost/s/* here to act as a domain.
        if ($request->is('s/*')) {
            $code = $request->segment(2);
            \Log::info($code);
            $shortUrlRepository = new ShortUrlRepository();
            $shortUrl = $shortUrlRepository->get($code);

            \Log::info($shortUrl);
            if (!empty($shortUrl) && !empty($shortUrl['redirect_url'])) {

                $clickData = [
                    'id' => $shortUrl['id'],
                    'occurred_at' => date('Y-m-d H:i:s'),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ];

                dispatch(new SaveShortUrlClick($clickData));

                return redirect($shortUrl['redirect_url']);
            }
        }

        return $next($request);
    }
}
