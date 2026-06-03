<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'leave_request_id',
        'file_name',
        'file_path',
    ];

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    /**
     * Clean up the stored file from disk when the Attachment
     * record is deleted, preventing orphaned files.
     */
    protected static function booted(): void
    {
        static::deleting(function (Attachment $attachment) {
            if ($attachment->file_path) {
                Storage::disk('local')->delete($attachment->file_path);
            }
        });
    }
}
