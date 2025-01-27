<?php

namespace App\Traits;

trait ShortUrl
{
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

    public function sanitizeUrl($url = null)
    {

        if (is_null($url)) {
            return null;
        }

        $url = filter_var($url, FILTER_SANITIZE_URL);

        // Check if the URL is valid
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        if (strpos($url, 'http://') === 0) {
            $url = 'https' . substr($url, 4);
        } elseif (strpos($url, 'https://') !== 0) {
            $url = 'https://' . ltrim($url, '/');
        }

        return $url;
    }
}
