# Codebase Audit Report: Employee Leave Management System (ELMS)

This report details the **Correctness**, **Security**, and **Efficiency** gaps identified in the current ELMS codebase. Each issue includes a detailed description, impact assessment, exact code references with clickable file links, and recommended remediation steps.

---

## Table of Contents
1. [Correctness Gaps](#1-correctness-gaps)
   - [1.1 Concurrency & Race Conditions in Leave Status Transitions](#11-concurrency--race-conditions-in-leave-status-transitions)
   - [1.2 Broken Carry-Forward Chain for Skip-Years](#12-broken-carry-forward-chain-for-skip-years)
   - [1.3 Retrospective Negative Balance (Double-Spending)](#13-retrospective-negative-balance-double-spending)
   - [1.4 Duplicate Balance Initialization Race Condition](#14-duplicate-balance-initialization-race-condition)
   - [1.5 Boolean Request Validation / Parsing Bug](#15-boolean-request-validation--parsing-bug)
   - [1.6 Stale Caching of Employee Report on Department Rename](#16-stale-caching-of-employee-report-on-department-rename)
2. [Security Gaps](#2-security-gaps)
   - [2.1 Missing Rate Limiting on Authentication Routes](#21-missing-rate-limiting-on-authentication-routes)
   - [2.2 Lack of Session Invalidation on Password Reset / Update](#22-lack-of-session-invalidation-on-password-reset--update)
3. [Efficiency Gaps](#3-efficiency-gaps)
   - [3.1 Non-Paginated Eager Loading in Employee Report (OOM Risk)](#31-non-paginated-eager-loading-in-employee-report-oom-risk)
   - [3.2 Inefficient Department Report Queries (Hydration Bottleneck)](#32-inefficient-department-report-queries-hydration-bottleneck)
   - [3.3 Dead Cache Keys and Observers](#33-dead-cache-keys-and-observers)

---

## 1. Correctness Gaps

### 1.1 Concurrency & Race Conditions in Leave Status Transitions

*   **Description**: In [ApprovalController](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Http/Controllers/ApprovalController.php), the `approve` and `reject` actions read the `status` of a `LeaveRequest` and execute state-changing business logic. Similarly, [LeaveController::cancel](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Http/Controllers/LeaveController.php#L143-L162) updates the request to `Cancelled`. However, none of these actions acquire a database-level lock (e.g., `lockForUpdate()`) on the `LeaveRequest` record itself.
*   **Impact**:
    1.  **Lost Updates / Race Conditions**: A manager can click "Approve" while the employee concurrently clicks "Cancel". If both operations read the database before either commits, both see the status as `'Pending'` and proceed.
    2.  **Stolen/Lost Balances**: The cancel transaction updates the request status to `'Cancelled'`. Since it reads the status as `'Pending'`, it does not refund the balance. Concurrently, the approval transaction deducts the user's leave balance, updates the status to `'Approved'`, and commits. The cancellation thread then commits and overwrites the status back to `'Cancelled'`. This results in the leave request ending up as `'Cancelled'` in the database while the leave days are permanently deducted from the user's balance and never refunded.
*   **Code Reference**:
    *   [ApprovalController.php:L50-L80 (approve)](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Http/Controllers/ApprovalController.php#L50-L80)
    *   [ApprovalController.php:L85-L108 (reject)](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Http/Controllers/ApprovalController.php#L85-L108)
    *   [LeaveController.php:L143-L162 (cancel)](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Http/Controllers/LeaveController.php#L143-L162)
*   **Remediation**: Acquire a pessimistic lock on the `LeaveRequest` row before checking or updating its status. For example, in `cancel()`:
    ```php
    DB::transaction(function () use ($leaveRequest) {
        $lockedRequest = LeaveRequest::where('id', $leaveRequest->id)->lockForUpdate()->firstOrFail();
        if ($lockedRequest->status === 'Approved') {
            $this->calculationService->refundBalance($lockedRequest);
        } elseif ($lockedRequest->status !== 'Pending') {
            throw new \Exception('Only pending or approved requests can be cancelled.');
        }
        $lockedRequest->update(['status' => 'Cancelled']);
    });
    ```

### 1.2 Broken Carry-Forward Chain for Skip-Years

*   **Description**: In [LeaveCalculationService::initializeBalances](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Services/LeaveCalculationService.php#L18-L55), the carry-forward logic calculates carried-over days by looking up the balance of exactly the prior year (`$year - 1`):
    ```php
    $previousBalance = $previousBalances->get($type->id);
    if ($previousBalance) {
        $carriedOver = $previousBalance->remaining_days;
    }
    ```
    If an employee does not request any leaves in year `Y-1`, their balance record for `Y-1` is never initialized. When they apply for leave in year `Y`, the system initializes year `Y` but finds no record for year `Y-1`. As a result, it carries forward `0` days.
*   **Impact**: Employees lose all accrued carry-forward leave balances from previous years if they skip taking leaves for even a single calendar year.
*   **Code Reference**: [LeaveCalculationService.php:L38-L44](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Services/LeaveCalculationService.php#L38-L44)
*   **Remediation**: Before initializing balances for year `Y`, check if prior years back to the employee's joining year have been initialized. Recursively initialize any missing years chronologically:
    ```php
    public function getOrCreateBalance(User $user, $leaveTypeId, $year)
    {
        $joiningYear = Carbon::parse($user->joining_date)->year;
        for ($y = $joiningYear; $y <= $year; $y++) {
            $balance = LeaveBalance::where('user_id', $user->id)
                ->where('leave_type_id', $leaveTypeId)
                ->where('year', $y)
                ->first();
            if (!$balance) {
                $this->initializeBalances($user, $y);
            }
        }
        return LeaveBalance::where('user_id', $user->id)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', $year)
            ->first();
    }
    ```

### 1.3 Retrospective Negative Balance (Double-Spending)

*   **Description**: The [LeaveBalance](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Models/LeaveBalance.php) observer propagates balance updates (deltas) to subsequent years automatically:
    ```php
    $nextBalance->allocated_days += $delta;
    $nextBalance->remaining_days += $delta;
    $nextBalance->save();
    ```
    If a user has already consumed their carried-forward leave in a future year (e.g. 2027), they can retrospectively apply for leave in the past year (e.g. 2026). The past year's deduction propagates a negative delta to the future year, driving the future year's remaining balance below zero without any validation checks.
*   **Impact**: Employees can double-spend leave balances (e.g., spending 15 days in 2027, then retrospectively claiming 10 days in 2026, which forces 2027 remaining balance to `-10`), violating company policies and causing accounting inconsistencies.
*   **Code Reference**: [LeaveBalance.php:L39-L58](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Models/LeaveBalance.php#L39-L58)
*   **Remediation**: In the observer or `deductBalance` method, add a check to verify that applying the delta will not result in a negative remaining balance for any subsequent initialized years. Throw an exception and abort the transaction if a violation occurs.

### 1.4 Duplicate Balance Initialization Race Condition

*   **Description**: In [LeaveCalculationService::getOrCreateBalance](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Services/LeaveCalculationService.php#L60-L78), the system checks if a balance exists. If not, it calls `initializeBalances()`, which loops over all leave types and inserts rows. There is no transaction lock or mutex to serialize this read-and-write operation.
*   **Impact**: If a user fires concurrent requests (e.g., applying for multiple leaves or loading the dashboard quickly), multiple threads will concurrently find no balance and attempt to call `initializeBalances()`. The first thread will insert the rows, and the second thread will attempt the same inserts, resulting in a database unique constraint violation (`QueryException` on the `['user_id', 'leave_type_id', 'year']` composite index) and a 500 error page.
*   **Code Reference**: [LeaveCalculationService.php:L67-L75](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Services/LeaveCalculationService.php#L67-L75)
*   **Remediation**: Wrap the balance check and creation in a transaction and use database locks or utilize a `firstOrCreate` / `insertOrIgnore` strategy.

### 1.5 Boolean Request Validation / Parsing Bug

*   **Description**: In [LeaveTypeController](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Http/Controllers/LeaveTypeController.php), the `carry_forward` field is validated as a boolean:
    ```php
    'carry_forward' => 'boolean',
    ```
    However, when saving, the controller uses:
    ```php
    'carry_forward' => $request->has('carry_forward'),
    ```
*   **Impact**: If a client (such as a JSON API, frontend component, or testing tool) explicitly submits `{"carry_forward": false}`, `$request->has('carry_forward')` evaluates to `true` (because the key exists in the request), causing the value to be stored as `true` (enabled) instead of `false`.
*   **Code Reference**:
    *   [LeaveTypeController.php:L44 (store)](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Http/Controllers/LeaveTypeController.php#L44)
    *   [LeaveTypeController.php:L74 (update)](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Http/Controllers/LeaveTypeController.php#L74)
*   **Remediation**: Use `$request->boolean('carry_forward')` instead of `$request->has('carry_forward')`.

### 1.6 Stale Caching of Employee Report on Department Rename

*   **Description**: The `reports.employees` cache stores the list of users with their departments. When a [Department](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Models/Department.php) is updated or deleted, the `departments.list` and `reports.departments` caches are cleared, but `reports.employees` is left intact.
*   **Impact**: If an administrator renames a department, the employee reports page continues to display the old/stale department name for up to 1 hour (until the cache expires).
*   **Code Reference**: [Department.php:L20-L29](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Models/Department.php#L20-L29)
*   **Remediation**: Clear `reports.employees` inside the `Department` model's boot observer:
    ```php
    $clearCache = function (Department $department) {
        \Illuminate\Support\Facades\Cache::forget('departments.list');
        \Illuminate\Support\Facades\Cache::forget('reports.departments');
        \Illuminate\Support\Facades\Cache::forget('reports.employees');
    };
    ```

---

## 2. Security Gaps

### 2.1 Missing Rate Limiting on Authentication Routes

*   **Description**: In [auth.php](file:///home/hrutav-modha/Documents/sem5/sbtp/project/routes/auth.php), guest routes such as `login`, `forgot-password`, and `reset-password` do not have any rate-limiting middleware applied.
*   **Impact**: Attackers can execute brute-force attacks on user credentials or abuse the password reset request endpoint to spam users' inboxes (email flooding) and cause denial-of-service (DoS) on mail delivery systems.
*   **Code Reference**: [auth.php:L14-L31](file:///home/hrutav-modha/Documents/sem5/sbtp/project/routes/auth.php#L14-L31)
*   **Remediation**: Apply Laravel's `throttle` middleware to these sensitive routes. For example:
    ```php
    Route::middleware('guest')->group(function () {
        Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1');
        Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:3,1')->name('password.email');
        Route::post('reset-password', [NewPasswordController::class, 'store'])->middleware('throttle:5,1')->name('password.store');
    });
    ```

### 2.2 Lack of Session Invalidation on Password Reset / Update

*   **Description**: When a user updates their password in [PasswordController](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Http/Controllers/Auth/PasswordController.php) or resets it in [NewPasswordController](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Http/Controllers/Auth/NewPasswordController.php), the system does not invalidate other active sessions for that user on other devices.
*   **Impact**: If a user's account is compromised and they change their password to secure it, the attacker's active session on another device remains logged in and authenticated until that session naturally expires.
*   **Code Reference**:
    *   [PasswordController.php:L23-L25](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Http/Controllers/Auth/PasswordController.php#L23-L25)
    *   [NewPasswordController.php:L45-L52](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Http/Controllers/Auth/NewPasswordController.php#L45-L52)
*   **Remediation**: Call `Auth::logoutOtherDevices($currentPassword)` during password updates to invalidate other active session tokens in the database/session store.

---

## 3. Efficiency Gaps

### 3.1 Non-Paginated Eager Loading in Employee Report (OOM Risk)

*   **Description**: In [ReportService::getEmployeeReport](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Services/ReportService.php#L12-L39), the code fetches the entire list of users and eager-loads all their departments and leave balances for the current year:
    ```php
    return User::with([
        'department',
        'leaveBalances' => function ($query) use ($currentYear) {
            $query->where('year', $currentYear)->with('leaveType');
        }
    ])->get();
    ```
*   **Impact**: In larger organizations (e.g., 5,000+ employees), loading thousands of bloated Eloquent objects and their relationships into memory at once causes extremely high RAM consumption, leading to PHP Out of Memory (OOM) crashes and high response latency.
*   **Code Reference**: [ReportService.php:L27-L33](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Services/ReportService.php#L27-L33)
*   **Remediation**: Use database-level aggregations (e.g., joining or selecting counts/sums) and implement pagination or chunking (`chunk()` / `lazy()`) to keep the memory footprint constant.

### 3.2 Inefficient Department Report Queries (Hydration Bottleneck)

*   **Description**: In [ReportService::getDepartmentReport](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Services/ReportService.php#L44-L103), the service loads all users associated with each department just to count them in PHP:
    ```php
    $departments = Department::with('users')->get();
    ...
    $totalEmployees = $dept->users->count();
    ```
*   **Impact**: This instantiates and hydrates every single user model in the database into memory just to run a count on the collection in PHP. This results in unnecessary database overhead and wasted memory.
*   **Code Reference**: [ReportService.php:L65-L68](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Services/ReportService.php#L65-L68)
*   **Remediation**: Use `withCount('users')` to perform the count aggregation at the SQL query level:
    ```php
    $departments = Department::withCount('users')->get();
    ...
    $totalEmployees = $dept->users_count;
    ```

### 3.3 Dead Cache Keys and Observers

*   **Description**: Multiple models define cache invalidation code for cache keys that are never set, read, or utilized anywhere in the application:
    *   `user.leaves.{$userId}` (forgotten in `LeaveRequest` and `User` observers)
    *   `approvals.pending.{$managerId}` (forgotten in `LeaveRequest` observer)
    *   `approvals.pending.admin` (forgotten in `LeaveRequest` observer)
    *   `employees.list` (forgotten in `User` observer)
*   **Impact**: Added lifecycle overhead on every database insert, update, or delete executing cache operations for keys that never contain data.
*   **Code Reference**:
    *   [LeaveRequest.php:L59-L67](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Models/LeaveRequest.php#L59-L67)
    *   [User.php:L143-L148](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Models/User.php#L143-L148)
*   **Remediation**: Remove the unused cache key invalidation lines from the model booted observers to clean up code and prevent unnecessary cache store operations.
