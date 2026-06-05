<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveRequest extends Model
{
    protected $fillable = [
        'user_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'days_requested',
        'reason',
        'status',
        'manager_comment',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function dates(): HasMany
    {
        return $this->hasMany(LeaveRequestDate::class);
    }

    protected static function booted(): void
    {
        $clearCache = function (LeaveRequest $request) {
            \Illuminate\Support\Facades\Cache::forget('reports.employees');
            \Illuminate\Support\Facades\Cache::forget('reports.departments');
            \Illuminate\Support\Facades\Cache::forget('reports.monthly');
        };

        static::saved($clearCache);
        
        static::created(function (LeaveRequest $request) {
            $actor = \Illuminate\Support\Facades\Auth::user() ? \Illuminate\Support\Facades\Auth::user()->email : 'System/CLI';
            \Illuminate\Support\Facades\Log::info("Audit log - Leave request submitted: ID={$request->id}, UserID={$request->user_id}, TypeID={$request->leave_type_id}, Days={$request->days_requested}, Actor={$actor}");

            if ($request->dates()->count() === 0) {
                $start = \Carbon\Carbon::parse($request->start_date)->startOfDay();
                $end = \Carbon\Carbon::parse($request->end_date)->startOfDay();
                $calcService = app(\App\Services\LeaveCalculationService::class);
                $workingDays = $calcService->getWorkingDays($start, $end);
                foreach ($workingDays as $date) {
                    $request->dates()->create([
                        'date' => $date->toDateString(),
                        'year' => $date->year,
                        'month' => $date->format('m'),
                    ]);
                }
            }
        });

        static::updated(function (LeaveRequest $request) {
            if ($request->isDirty('status')) {
                $oldStatus = $request->getOriginal('status');
                $newStatus = $request->status;
                $actor = \Illuminate\Support\Facades\Auth::user() ? \Illuminate\Support\Facades\Auth::user()->email : 'System/CLI';
                \Illuminate\Support\Facades\Log::info("Audit log - Leave request status changed: ID={$request->id}, UserID={$request->user_id}, From={$oldStatus}, To={$newStatus}, Actor={$actor}");
            }
        });

        static::deleted(function (LeaveRequest $request) use ($clearCache) {
            $clearCache($request);
            $actor = \Illuminate\Support\Facades\Auth::user() ? \Illuminate\Support\Facades\Auth::user()->email : 'System/CLI';
            \Illuminate\Support\Facades\Log::info("Audit log - Leave request deleted: ID={$request->id}, UserID={$request->user_id}, Actor={$actor}");
        });

        /**
         * Delete attachment files from disk before the database
         * cascade removes the attachment rows. Each Attachment's own
         * deleting event handles the actual file deletion.
         */
        static::deleting(function (LeaveRequest $leaveRequest) {
            $leaveRequest->attachments->each(function (Attachment $attachment) {
                $attachment->delete();
            });
        });
    }
}
