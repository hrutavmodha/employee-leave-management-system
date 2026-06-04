<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    protected $fillable = [
        'user_id',
        'leave_type_id',
        'year',
        'allocated_days',
        'used_days',
        'remaining_days',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    protected static function booted(): void
    {
        $clearCache = function (LeaveBalance $balance) {
            \Illuminate\Support\Facades\Cache::forget('user.balances.' . $balance->user_id . '.' . $balance->year);
            \Illuminate\Support\Facades\Cache::forget('reports.employees');
        };

        static::saved($clearCache);
        static::deleted($clearCache);

        static::updated(function (LeaveBalance $balance) {
            if ($balance->isDirty('remaining_days') && $balance->leaveType && $balance->leaveType->carry_forward) {
                $oldRemaining = $balance->getOriginal('remaining_days');
                $newRemaining = $balance->remaining_days;
                $delta = $newRemaining - $oldRemaining;

                if ($delta != 0) {
                    $nextBalance = LeaveBalance::where('user_id', $balance->user_id)
                        ->where('leave_type_id', $balance->leave_type_id)
                        ->where('year', $balance->year + 1)
                        ->first();

                    if ($nextBalance) {
                        $nextBalance->allocated_days += $delta;
                        $nextBalance->remaining_days += $delta;
                        $nextBalance->save();
                    }
                }
            }
        });
    }
}
