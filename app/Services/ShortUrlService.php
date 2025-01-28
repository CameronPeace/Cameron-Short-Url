<?php

namespace App\Services;

use App\Exceptions\ShortUrlServiceException;
use App\Models\Repositories\ShortUrlRepository;
use App\Traits\ShortUrl;

class ShortUrlService
{
    use ShortUrl;

    private $shortUrlRepository;

    public function __construct()
    {
        $this->shortUrlRepository = new ShortUrlRepository();
    }

    /**
     * Create a new short_url record with a unique code.
     *
     * @param string $redirect The redirect url.
     * @param string|null $domain The desired short url domain. Do not use if custom domains do not exist.
     * @param int $length The length of the code.
     *
     * @return array
     */
    public function createShortUrl(string $redirect, string $domain = null, int $length = 6)
    {
        try {
            $code = $this->createNewCode($domain, $length);

            return $this->shortUrlRepository->create($code, $redirect, $domain);
        } catch (\Exception $e) {
            throw new ShortUrlServiceException($e->getMessage());
        }
    }

    /**
     * Create a new unique short url.
     *
     * @param string|null $domain The domain to create the code under.
     * @param int $length The desired link of the code.
     *
     * @return string $code
     */
    public function createNewCode(string $domain = null, int $length = 6)
    {
        try {
            $tries = 0;
            $code = $this->generateRandomString($length);
            $found = $this->shortUrlRepository->get($code, $domain);

            while (!empty($found)) {
                $code = $this->generateRandomString($length);
                $found = $this->shortUrlRepository->get($code, $domain);
                $tries++;

                if ($tries >= 50) {
                    $message = sprintf('Failed to create unique short url for the domain %s', $domain);
                    throw new \Exception($message);
                }
            }

            return $code;
        } catch (\Exception $e) {
            throw new ShortUrlServiceException($e->getMessage());
        }
    }

    /**
     * Retrieve the details of a short url.
     *
     * @param string $code
     * @param string|null $domain
     *
     * @return array
     */
    public function getCodeDetails(string $code, string $domain = null)
    {
        try {
            return $this->shortUrlRepository->getDetailsByCode($code, $domain);
        } catch (\Exception $e) {
            throw new ShortUrlServiceException($e->getMessage());
        }
    }
}
