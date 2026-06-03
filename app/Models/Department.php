<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = ['name', 'description'];

    /**
     * Get the employees (users) for the department.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    protected static function booted(): void
    {
        $clearCache = function (Department $department) {
            \Illuminate\Support\Facades\Cache::forget('departments.list');
            \Illuminate\Support\Facades\Cache::forget('reports.departments');
        };

        static::saved($clearCache);
        static::deleted($clearCache);
    }
}
