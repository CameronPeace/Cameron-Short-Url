<?php
namespace App\Models\Repositories;

use App\Models\ShortUrl;

class ShortUrlRepository {

    protected $model;

    public function __construct()
    {
        $this->model = new ShortUrl;
    }

    public function get(string $code, string $domain = null)
    {
        return $this->model->where('domain', $domain)->where('code', $code)->first();   
    }
}