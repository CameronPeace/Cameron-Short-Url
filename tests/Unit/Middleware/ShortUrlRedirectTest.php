<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\ShortUrlRedirect;
use App\Models\ShortUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class ShortUrlRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test the handle function finds and uses the cached resource.
     */
    public function testHandleWithCachedRedirect()
    {
        $request = Mockery::mock(Request::class);
        $next = function ($request) {
            return response('Next middleware');
        };

        $request->shouldReceive('is')->with('s/*')->andReturn(true);
        $request->shouldReceive('getHost')->andReturn('localhost');
        $request->shouldReceive('segment')->with(2)->andReturn('abc123');
        $request->shouldReceive('ip')->andReturn('127.0.0.1');
        $request->shouldReceive('userAgent')->andReturn('TestAgent');

        Cache::shouldReceive('get')->with('localhost_abc123')->andReturn('https://example.com');
        Cache::shouldReceive('put')->with('localhost_abc123', 'https://example.com', 600)->never();

        $middleware = new ShortUrlRedirect();

        $response = $middleware->handle($request, $next);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('https://example.com', $response->headers->get('Location'));
    }

    /**
     * Test the handle function uses the database before redirecting.
     */
    public function testHandleWithDatabaseRedirect()
    {
        $request = Mockery::mock(Request::class);
        $next = function ($request) {
            return response('Next middleware');
        };

        $code = 'xypafpm';

        $request->shouldReceive('is')->with('s/*')->andReturn(true);
        $request->shouldReceive('getHost')->andReturn('localhost');
        $request->shouldReceive('segment')->with(2)->andReturn($code);
        $request->shouldReceive('ip')->andReturn('127.0.0.1');
        $request->shouldReceive('userAgent')->andReturn('TestAgent');

        Cache::shouldReceive('get')->with('localhost_' . $code)->andReturn(null);
        Cache::shouldReceive('put')->with('localhost_' . $code, 'https://example.com', 600)->once();

        $shortUrl = new ShortUrl();
        $shortUrl->create(['redirect' => 'https://example.com', 'domain' => null, 'code' => $code]);

        $middleware = new ShortUrlRedirect();

        $response = $middleware->handle($request, $next);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('https://example.com', $response->headers->get('Location'));
    }

    /**
     * Test the occurance where a short url is not found.
     *
     * @return void
     */
    public function testHandleWithoutShortUrl()
    {
        $request = Mockery::mock(Request::class);
        $next = function ($request) {
            return response('Next middleware');
        };

        $request->shouldReceive('is')->with('s/*')->andReturn(true);
        $request->shouldReceive('getHost')->andReturn('localhost');
        $request->shouldReceive('segment')->with(2)->andReturn('abc123');
        $request->shouldReceive('ip')->andReturn('127.0.0.1');
        $request->shouldReceive('userAgent')->andReturn('Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:135.0) Gecko/20100101 Firefox/135.0');


        Cache::shouldReceive('get')->with('localhost_abc123')->andReturn(null);
        Cache::shouldReceive('put')->never();

        $middleware = new ShortUrlRedirect();

        $response = $middleware->handle($request, $next);

        $this->assertEquals('Next middleware', $response->getContent());
    }

    /**
     * Test routes without the prefix to function as normal.
     */
    public function testHandleWithoutMatchingPath()
    {
        $request = Mockery::mock(Request::class);
        $next = function ($request) {
            return response('Next middleware');
        };

        $request->shouldReceive('is')->with('s/*')->andReturn(false);

        $middleware = new ShortUrlRedirect();

        $response = $middleware->handle($request, $next);

        $this->assertEquals('Next middleware', $response->getContent());
    }
}
