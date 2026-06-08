<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $fillable = [
        'name',
        'allowed_days',
        'carry_forward',
        'description',
    ];

    protected $casts = [
        'carry_forward' => 'boolean',
    ];

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    protected static function booted(): void
    {
        $clearCache = function (LeaveType $leaveType) {
            \Illuminate\Support\Facades\Cache::forget('leave_types.all');
        };

        $logChange = function (LeaveType $type, string $action) {
            $actor = \Illuminate\Support\Facades\Auth::user() ? \Illuminate\Support\Facades\Auth::user()->email : 'System/CLI';
            \Illuminate\Support\Facades\Log::info("Audit log - Leave policy/type {$action}: ID={$type->id}, Name={$type->name}, Actor={$actor}");
        };

        static::saved(function (LeaveType $leaveType) use ($clearCache, $logChange) {
            $clearCache($leaveType);
            \App\Services\ReportCacheHelper::invalidateEmployeeReportCache();
            $action = $leaveType->wasRecentlyCreated ? 'created' : 'updated';
            $logChange($leaveType, $action);
        });

        static::deleted(function (LeaveType $leaveType) use ($clearCache, $logChange) {
            $clearCache($leaveType);
            \App\Services\ReportCacheHelper::invalidateEmployeeReportCache();
            $logChange($leaveType, 'deleted');
        });
    }
}
