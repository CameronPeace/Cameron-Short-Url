<?php

namespace App\Models\Repositories;

use App\Models\ShortUrl;

class ShortUrlRepository
{

    protected $model;

    public function __construct()
    {
        $this->model = new ShortUrl;
    }

    /**
     * Get a specific short url by a specific code and domain.
     *
     * @param string $code
     * @param string|null $domain
     *
     * @return array
     */
    public function first(string $code, string $domain = null)
    {
        return $this->model->where('domain', $domain)->where('code', $code)->first();
    }

    /**
     * Create a new short_url record.
     *
     * @param string $code
     * @param string $redirect
     * @param string|null $domain
     *
     * @return array
     */
    public function create(string $code, string $redirect, string $domain = null)
    {
        return $this->model->create(['code' => $code, 'redirect' => $redirect, 'domain' => $domain]);
    }

    /**
     * Get the details on a specific url.
     *
     * @param string $code
     * @param string|null $domain
     *
     * @return array
     */
    public function getDetailsByCode(string $code, string $domain = null)
    {
        return $this->model->select('short_url.code', 'short_url.redirect', 'short_url.created_at')
            ->selectRaw('COUNT(short_url_click.id) AS total_clicks')
            ->leftJoin('short_url_click', 'short_url.id', 'short_url_click.short_url_id')
            ->where('code', $code)
            ->where('domain', $domain)
            ->groupBy('short_url.id')
            ->first();
    }

    /**
     * Return a list of short_urls and their click totals.
     *
     * @param int $limit
     * @param bool $orderByClicks
     *
     * @return array
     */
    public function getRedirects(int $limit = 100, bool $orderByClicks = true)
    {
        $query = $this->model->select('short_url.code', 'short_url.redirect', 'short_url.created_at')
            ->selectRaw('COUNT(short_url_click.id) AS total_clicks')
            ->leftJoin('short_url_click', 'short_url.id', 'short_url_click.short_url_id')
            ->groupBy('short_url.id')
            ->limit($limit);

        if ($orderByClicks) {
            $query->orderBy('total_clicks', 'desc');
        } else {
            $query->orderBy('short_url.id');
        }

        return $query->get();
    }
}
