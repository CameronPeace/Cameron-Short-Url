<?php

namespace App\Services;

use App\Exceptions\ShortUrlServiceException;
use App\Models\Repositories\ShortUrlRepository;
use App\Traits\ShortUrl;

class ShortUrlService
{
    use ShortUrl;

    /**
     * @var ShortUrlRepository
     */
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
     * @throws ShortUrlServiceException
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
     * @throws ShortUrlServiceException
     */
    public function createNewCode(string $domain = null, int $length = 6)
    {
        try {
            $tries = 0;
            $code = $this->generateRandomString($length);
            $found = $this->shortUrlRepository->first($code, $domain);

            while (!empty($found)) {
                $code = $this->generateRandomString($length);
                $found = $this->shortUrlRepository->first($code, $domain);
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
     * @throws ShortUrlServiceException
     */
    public function getCodeDetails(string $code, string $domain = null)
    {
        try {
            return $this->shortUrlRepository->getDetailsByCode($code, $domain);
        } catch (\Exception $e) {
            throw new ShortUrlServiceException($e->getMessage());
        }
    }

    /**
     * Retrieve a single short url
     *
     * @param string $code
     * @param string|null $domain
     *
     * @return array
     * @throws ShortUrlServiceException
     */
    public function getShortUrl(string $code, string $domain = null)
    {

        if (!is_null($domain) && !in_array($domain, self::DOMAINS)) {
            $domain = null;
        }

        try {
            return $this->shortUrlRepository->first($code, $domain);
        } catch (\Exception $e) {
            throw new ShortUrlServiceException($e->getMessage());
        }
    }

    /**
     * Get a list of redirects ordered by their clicks.
     *
     * @param int $limit Max records to return
     * @param bool $orderByClicks Order results by the highest click counts.
     *
     * @return array
     * @throws ShortUrlServiceException
     */
    public function getRedirects(int $limit = 100, bool $orderByClicks = true)
    {
        try {
            return $this->shortUrlRepository->getRedirects($limit, $orderByClicks);
        } catch (\Exception $e) {
            throw new ShortUrlServiceException($e->getMessage());
        }
    }
}
