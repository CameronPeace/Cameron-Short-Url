<?php

namespace App\Services;

use App\Exceptions\ShortUrlServiceException;
use App\Models\Repositories\ShortUrlRepository;

class ShortUrlService
{

    private $shortUrlRepository;

    public function __construct()
    {
        $this->shortUrlRepository = new ShortUrlRepository();
    }

    public function createShortUrl(string $redirect, string $domain = null, int $length = 6)
    {
        try {
            $code = $this->createNewCode($length, $domain);

            return $this->shortUrlRepository->create($code, $redirect);
        } catch (\Exception $e) {
            throw new ShortUrlServiceException($e->getMessage());
        }
    }

    public function createNewCode(int $length = 6, string $domain = null)
    {
        try {
            $code = $this->generateRandomString($length);
            $found = $this->shortUrlRepository->get($code, $domain);

            if (!empty($found)) {
                return $this->createNewCode($length, $domain);
            }

            return $code;
        } catch (\Exception $e) {
            throw new ShortUrlServiceException($e->getMessage());
        }
    }

    public function getCodeDetails(string $code, string $domain = null)
    {
        try {
            return $this->shortUrlRepository->getDetailsByCode($code, $domain);
        } catch (\Exception $e) {
            throw new ShortUrlServiceException($e->getMessage());
        }
    }


    public function generateRandomString($length = 10)
    {

        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }
}
