<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequestDate extends Model
{
    protected $fillable = [
        'leave_request_id',
        'date',
        'year',
        'month',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get the leave request that owns this date record.
     */
    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }
}
