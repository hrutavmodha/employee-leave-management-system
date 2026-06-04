<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'department_id',
        'manager_id',
        'designation',
        'joining_date',
        'status',
        'profile_picture',
    ];

    /**
     * Get and set the user's role with titlecase normalization.
     */
    protected function role(): Attribute
    {
        return Attribute::make(
            get: function (string $value) {
                $mapped = [
                    'employee' => 'Employee',
                    'manager' => 'Manager',
                    'hr/admin' => 'HR/Admin',
                ];
                return $mapped[strtolower($value)] ?? $value;
            },
            set: function (string $value) {
                $mapped = [
                    'employee' => 'Employee',
                    'manager' => 'Manager',
                    'hr/admin' => 'HR/Admin',
                ];
                return $mapped[strtolower($value)] ?? $value;
            }
        );
    }

    /**
     * Get the full name.
     */
    public function getNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    /**
     * Role checks.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'HR/Admin';
    }

    public function isManager(): bool
    {
        return $this->role === 'Manager';
    }

    public function isEmployee(): bool
    {
        return $this->role === 'Employee';
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'joining_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        $clearCache = function (User $user) {
            \Illuminate\Support\Facades\Cache::forget('employees.list');
            \Illuminate\Support\Facades\Cache::forget('departments.list');
            \Illuminate\Support\Facades\Cache::forget('reports.employees');
            \Illuminate\Support\Facades\Cache::forget('reports.departments');
            \Illuminate\Support\Facades\Cache::forget('user.leaves.' . $user->id);
            \Illuminate\Support\Facades\Cache::forget('user.balances.' . $user->id . '.' . date('Y'));
        };

        static::saved($clearCache);
        
        static::created(function (User $user) {
            $actor = \Illuminate\Support\Facades\Auth::user() ? \Illuminate\Support\Facades\Auth::user()->email : 'System/CLI';
            \Illuminate\Support\Facades\Log::info("Audit log - Employee created: ID={$user->id}, Email={$user->email}, Role={$user->role}, Actor={$actor}");
        });

        static::deleted(function (User $user) use ($clearCache) {
            $clearCache($user);
            $actor = \Illuminate\Support\Facades\Auth::user() ? \Illuminate\Support\Facades\Auth::user()->email : 'System/CLI';
            \Illuminate\Support\Facades\Log::info("Audit log - Employee deleted: ID={$user->id}, Email={$user->email}, Role={$user->role}, Actor={$actor}");
        });

        /**
         * Prune orphaned storage assets before the database cascade
         * removes child rows (which would bypass Eloquent events).
         *
         * 1. Delete profile picture from the public disk.
         * 2. Iterate child LeaveRequests and delete them via Eloquent
         *    so that their own `deleting` events fire and cascade
         *    file cleanup down to Attachment records.
         */
        static::deleting(function (User $user) {
            if ($user->profile_picture) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_picture);
            }

            $user->leaveRequests->each(function (LeaveRequest $leaveRequest) {
                $leaveRequest->delete();
            });
        });
    }
}
