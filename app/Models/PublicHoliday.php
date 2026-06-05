<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicHoliday extends Model
{
    protected $fillable = [
        'name',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    protected static function booted()
    {
        $clearCache = function () {
            \App\Services\ReportCacheHelper::invalidateEmployeeReportCache();
            \Illuminate\Support\Facades\Cache::forget('reports.departments');
            \Illuminate\Support\Facades\Cache::forget('reports.monthly');
        };

        $logHoliday = function (PublicHoliday $holiday, string $action) {
            $actor = \Illuminate\Support\Facades\Auth::user() ? \Illuminate\Support\Facades\Auth::user()->email : 'System/CLI';
            $dateStr = $holiday->date instanceof \Carbon\Carbon ? $holiday->date->format('Y-m-d') : $holiday->date;
            \Illuminate\Support\Facades\Log::info("Audit log - Public holiday {$action}: ID={$holiday->id}, Name={$holiday->name}, Date={$dateStr}, Actor={$actor}");
        };

        static::saved(function (PublicHoliday $holiday) use ($clearCache, $logHoliday) {
            $clearCache();
            $action = $holiday->wasRecentlyCreated ? 'created' : 'updated';
            $logHoliday($holiday, $action);
        });

        static::deleted(function (PublicHoliday $holiday) use ($clearCache, $logHoliday) {
            $clearCache();
            $logHoliday($holiday, 'deleted');
        });
    }
}
