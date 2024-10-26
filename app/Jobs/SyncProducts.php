<?php

namespace App\Jobs;

use Artisan;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Queue\Queueable;

class SyncProducts implements ShouldQueueAfterCommit
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Artisan::call('scout:import App\\\Models\\\Product');
    }
}
