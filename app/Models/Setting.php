<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get a setting value by key, returning a default value if not set.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getVal(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        if (!$setting) {
            return $default;
        }

        $decoded = json_decode($setting->value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $setting->value;
    }

    /**
     * Set a setting value by key.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function setVal(string $key, $value): void
    {
        $encodedValue = is_array($value) || is_object($value) ? json_encode($value) : $value;
        self::updateOrCreate(
            ['key' => $key],
            ['value' => $encodedValue]
        );
    }

    protected static function booted()
    {
        $clearCache = function () {
            \App\Services\ReportCacheHelper::invalidateEmployeeReportCache();
            \Illuminate\Support\Facades\Cache::forget('reports.departments');
            \Illuminate\Support\Facades\Cache::forget('reports.monthly');
        };

        $logSetting = function (Setting $setting, string $action) {
            $actor = \Illuminate\Support\Facades\Auth::user() ? \Illuminate\Support\Facades\Auth::user()->email : 'System/CLI';
            \Illuminate\Support\Facades\Log::info("Audit log - Setting {$action}: ID={$setting->id}, Key={$setting->key}, Actor={$actor}");
        };

        static::saved(function (Setting $setting) use ($clearCache, $logSetting) {
            $clearCache();
            $action = $setting->wasRecentlyCreated ? 'created' : 'updated';
            $logSetting($setting, $action);
        });

        static::deleted(function (Setting $setting) use ($clearCache, $logSetting) {
            $clearCache();
            $logSetting($setting, 'deleted');
        });
    }
}
