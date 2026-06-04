<?php
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DepartmentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', function () {
    $user = Auth::user();
    $currentYear = date('Y');
    
    $stats = [
        'pending' => $user->leaveRequests()->where('status', 'Pending')->count(),
        'approved' => $user->leaveRequests()->where('status', 'Approved')->whereYear('start_date', $currentYear)->sum('days_requested'),
    ];

    $pendingApprovals = 0;
    if ($user->isManager() || $user->isAdmin()) {
        if ($user->isAdmin()) {
            $pendingApprovals = \App\Models\LeaveRequest::where('status', 'Pending')->count();
        } else {
            $pendingApprovals = \App\Models\LeaveRequest::whereHas('user', function($q) use ($user) {
                $q->where('manager_id', $user->id);
            })->where('status', 'Pending')->count();
        }
    }

    return view('dashboard', compact('stats', 'pendingApprovals'));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/documentation', function () {
        return view('documentation');
    })->name('documentation');

    // Leave Requests (All Users)
    Route::get('/leaves', [LeaveController::class, 'index'])->name('leaves.index');
    Route::get('/leaves/apply', [LeaveController::class, 'create'])->name('leaves.create');
    Route::post('/leaves', [LeaveController::class, 'store'])->name('leaves.store');
    Route::post('/leaves/{leaveRequest}/cancel', [LeaveController::class, 'cancel'])->name('leaves.cancel');
    Route::get('/leaves/{leaveRequest}/attachments/{attachment}', [LeaveController::class, 'viewAttachment'])->name('leaves.attachment');

    // Approval Workflow (Managers and HR/Admin)
    Route::middleware('role:Manager,HR/Admin')->group(function () {
        Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
        Route::post('/approvals/{leaveRequest}/approve', [ApprovalController::class, 'approve'])->name('approvals.approve');
        Route::post('/approvals/{leaveRequest}/reject', [ApprovalController::class, 'reject'])->name('approvals.reject');
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    });

    // Admin Specific Routes
    Route::middleware('role:HR/Admin')->group(function () {
        Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');

        Route::resource('leave-types', LeaveTypeController::class);
        Route::resource('departments', DepartmentController::class);
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

        // Holiday & Weekend Settings
        Route::get('/settings/holidays', [\App\Http\Controllers\HolidaySettingController::class, 'index'])->name('settings.holidays');
        Route::post('/settings/holidays', [\App\Http\Controllers\HolidaySettingController::class, 'store'])->name('settings.holidays.store');
        Route::post('/settings/week-holidays', [\App\Http\Controllers\HolidaySettingController::class, 'updateWeekHolidays'])->name('settings.week_holidays.update');
        Route::delete('/settings/holidays/{publicHoliday}', [\App\Http\Controllers\HolidaySettingController::class, 'destroy'])->name('settings.holidays.destroy');
    });
});

require __DIR__.'/auth.php';
