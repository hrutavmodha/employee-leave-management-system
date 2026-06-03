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

    protected static function booted(): void
    {
        $clearCache = function (LeaveRequest $request) {
            \Illuminate\Support\Facades\Cache::forget('user.leaves.' . $request->user_id);
            \Illuminate\Support\Facades\Cache::forget('reports.employees');
            \Illuminate\Support\Facades\Cache::forget('reports.departments');
            \Illuminate\Support\Facades\Cache::forget('reports.monthly');
            
            if ($request->user) {
                \Illuminate\Support\Facades\Cache::forget('approvals.pending.' . $request->user->manager_id);
            }
            \Illuminate\Support\Facades\Cache::forget('approvals.pending.admin');
        };

        static::saved($clearCache);
        static::deleted($clearCache);

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
