<?php

namespace App\Traits;

use App\Models\JobLog;

trait LoggableJob
{
    public function log(string $job, string $type, array $log = null)
    {
        $jobLog = new JobLog();
        $jobLog->create([
            'job' => $job,
            'type' => $type,
            'log' => json_encode($log)
        ]);
    }
}
