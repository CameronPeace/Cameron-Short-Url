<?php

namespace Tests\Unit;

use App\Exceptions\ShortUrlServiceException;
use App\Models\ShortUrl;
use App\Models\ShortUrlClick;
use App\Services\ShortUrlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShortUrlServiceTest extends TestCase
{
    use RefreshDatabase;

    /** The class instance we want to test */
    private $class;

    public function setUp(): void
    {
        parent::setUp();
        $this->class = new ShortUrlService();
    }

    /**
     * Test that generateRandomString lives up to its name.
     */
    public function testGenerateRandomString()
    {
        // Test with default length
        $randomString = $this->class->generateRandomString();
        $this->assertEquals(10, strlen($randomString));

        // Test with custom length
        $customLength = 15;
        $randomString = $this->class->generateRandomString($customLength);
        $this->assertEquals($customLength, strlen($randomString));

        // assert the random string does not have any numbers
        $this->assertEquals(preg_match('~[0-9]+~', $randomString), 0);

        // assert the random string does not have any special characters
        $this->assertEquals(preg_match('/[^a-zA-Z0-9]/', $randomString), 0);
    }

    /**
     * Test that the sanitizeUrl function handles urls properly.
     */
    public function testSanitizeUrl()
    {
        // Test with null input
        $this->assertNull($this->class->sanitizeUrl(null));

        // Test with invalid URL
        $this->assertFalse($this->class->sanitizeUrl('invalid_url'));

        // Test with http URL
        $url = 'http://example.com';
        $sanitizedUrl = $this->class->sanitizeUrl($url);
        $this->assertEquals('https://example.com', $sanitizedUrl);

        // Test with https URL
        $url = 'https://example.com';
        $sanitizedUrl = $this->class->sanitizeUrl($url);
        $this->assertEquals('https://example.com', $sanitizedUrl);

        // Test with URL without scheme
        $url = 'example.com';
        $sanitizedUrl = $this->class->sanitizeUrl($url);
        $this->assertEquals('https://example.com', $sanitizedUrl);
    }

    /**
     * Test the createNewCode function can create unique codes.
     */
    public function testCreateNewCode()
    {
        $length = 6;
        $code = $this->class->createNewCode(null, $length);

        $this->assertNotEmpty($code);
        $this->assertEquals(strlen($code), $length);
    }

    /**
     * Test that the creatNewCode function will throw an exception when unable to make a unique code.
     */
    public function testCreateNewCodeCanEscapeLoop()
    {
        $domain = 'sandpaper.com';
        $length = 1;

        $this->expectException(ShortUrlServiceException::class);
        $this->expectExceptionMessage('Failed to create unique short url for the domain ' . $domain);

        $characters = explode(',', 'a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z');
        $shortUrlModel = new ShortUrl();

        // create a code for every letter of the alphabet.
        foreach ($characters as $character) {
            $shortUrlModel->create(['domain' => $domain, 'code' => $character, 'redirect' => 'https://www.test.com']);
        }

        // without any possible codes to create an exception should be thrown.
        $this->class->createNewCode($domain, $length);
    }

    /**
     * Test the createShortUrl function results in a new short_url record.
     */
    public function testCreateShortUrlSavesRecord()
    {
        $redirect = 'https://www.example.com';
        $domain = 'smol.com';

        $record = $this->class->createShortUrl($redirect, $domain);

        $this->assertNotEmpty($record);
        $this->assertEquals($record['redirect'], $redirect);
        $this->assertEquals($record['domain'], $domain);

        $shortUrlModel = new ShortUrl();
        $newRecord = $shortUrlModel->select('code')->where('domain', $domain)->where('code', $record['code'])->first();

        $this->assertEquals($record['code'], $newRecord['code']);
    }

    public function testGetCodeDetails()
    {

        $domain = 'details.io';
        $redirect = 'test.com';
        $totalClicks = 5;

        $shortUrl = new ShortUrl();
        $record = $shortUrl->create([
            'domain' => $domain,
            'redirect' => $redirect,
            'code' => $this->class->generateRandomString(5)
        ]);
        
        $this->createClicks($record['id'], $totalClicks);

        $details = $this->class->getCodeDetails($record['code'], $domain);
        
        $this->assertNotEmpty($details);
        $this->assertArrayHasKey('total_clicks', $details);
        $this->assertEquals($details['total_clicks'], $totalClicks);
    }

    /**
     * Populate the short_url_clicks table with records for a short_url.
     *
     * @param int $shortUrlId
     * @param int $total
     *
     * @return void
     */
    private function createClicks($shortUrlId, $total = 5)
    {
        $shortUrlClick = new ShortUrlClick();

        for($i = 0; $i < $total; $i++) {
            $shortUrlClick->create([
                'short_url_id' => $shortUrlId,
                'occurred_at' => date('Y-m-d H:i:s'),
                'ip_address' => '127.0.0.1', 
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:135.0) Gecko/20100101 Firefox/135.0'
            ]);
        }
    }
}
