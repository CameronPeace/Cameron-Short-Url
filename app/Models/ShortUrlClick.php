<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShortUrlClick extends Model
{

    protected $table = 'short_url_click';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ip_address',
        'user_agent'
    ];

    /**
     * The attributes that shouald be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'occurred_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
