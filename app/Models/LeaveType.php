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

        static::saved($clearCache);
        static::deleted($clearCache);
    }
}
