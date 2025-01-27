<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobLog extends Model
{
    protected $table = 'job_log';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'job',
        'type',
        'log'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
