<?php

namespace App\Services;

class ReportCacheHelper
{
    /**
     * Atomically invalidate all cached employee report pages.
     *
     * Bumps the version counter so subsequent reads use a new cache key
     * namespace. Previously-cached pages become unreachable and expire
     * via their TTL (3600s), preventing orphan key accumulation.
     */
    public static function invalidateEmployeeReportCache(): void
    {
        $store = \Illuminate\Support\Facades\Cache::getStore();

        if (method_exists($store, 'increment')) {
            \Illuminate\Support\Facades\Cache::increment('reports.employees.version');
        } else {
            $current = \Illuminate\Support\Facades\Cache::get('reports.employees.version', 1);
            \Illuminate\Support\Facades\Cache::put('reports.employees.version', $current + 1, 7200);
        }
    }
}
