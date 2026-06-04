<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\Event;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(JobQueued::class, function (JobQueued $event) {
            $threshold = config('queue.flush_threshold', 1);
            $jobCount = DB::table('jobs')->count();

            if ($jobCount >= $threshold) {
                // Asynchronously run the queue worker in the background
                // --stop-when-empty runs until all queued jobs are flushed and then terminates
                $command = 'php ' . base_path('artisan') . ' queue:work --stop-when-empty > /dev/null 2>&1 &';
                exec($command);
            }
        });
    }
}
