<?php
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Leave Requests (All Users)
    Route::get('/leaves', [LeaveController::class, 'index'])->name('leaves.index');
    Route::get('/leaves/apply', [LeaveController::class, 'create'])->name('leaves.create');
    Route::post('/leaves', [LeaveController::class, 'store'])->name('leaves.store');
    Route::post('/leaves/{leaveRequest}/cancel', [LeaveController::class, 'cancel'])->name('leaves.cancel');

    // Approval Workflow (Managers and HR/Admin)
    Route::middleware('role:Manager,HR/Admin')->group(function () {
        Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
        Route::post('/approvals/{leaveRequest}/approve', [ApprovalController::class, 'approve'])->name('approvals.approve');
        Route::post('/approvals/{leaveRequest}/reject', [ApprovalController::class, 'reject'])->name('approvals.reject');
    });

    // Admin Specific Routes
    Route::middleware('role:HR/Admin')->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');

        Route::resource('leave-types', LeaveTypeController::class);
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    });
});

require __DIR__.'/auth.php';
