# Employee Leave Management System (ELMS) - Audit Report

This report outlines the correctness, efficiency, and security gaps identified during the code audit of the ELMS application. Each issue is detailed with its corresponding file locations, impact analysis, and actionable remediation steps.

---

## 1. Correctness Gaps

### 1.1 Inconsistent Leave Counts due to Dynamic Weekend/Holiday Changes
* **Location:** 
  * [ReportService.php](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Services/ReportService.php#L31-L39) (in `getEmployeeReport` and `getDepartmentReport`)
  * [LeaveController.php](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Http/Controllers/LeaveController.php#L122-L134) (in `cancel`)
  * [web.php](file:///home/hrutav-modha/Documents/sem5/sbtp/project/routes/web.php#L15-L39) (in `/dashboard` statistics)
* **Problem:** 
  * When an employee submits a leave request, the working days requested are calculated via `calculateDaysPerYear` and stored statically in the `days_requested` field in [LeaveRequest.php](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Models/LeaveRequest.php).
  * The dashboard calculates total approved leaves by summing the static `days_requested` field.
  * In contrast, the reporting service ([ReportService.php](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Services/ReportService.php)) calculates leaves on the fly by calling `getWorkingDaysDistribution()`, which reads the current `week_holidays` setting dynamically.
  * Furthermore, when cancelling an approved leave, [LeaveController.php](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Http/Controllers/LeaveController.php) recalculates the refund duration dynamically via `refundBalance()`.
* **Impact:** 
  * If weekend or public holidays are updated by an admin, reports and dashboard statistics will show different and conflicting leave durations for the same historical leave requests.
  * More critically, cancelling a previously approved leave request will recalculate the refund using the *new* weekend settings. If settings changed since approval, the refunded balance will mismatch the deducted balance, corrupting user leave balances.
* **Remediation:** 
  * Store the exact day breakdown per year/month statically in a pivot or child table (e.g., `leave_request_dates`) at submission time.
  * Use these persisted dates for approvals, refunds, and reports rather than recalculating dates dynamically on the fly.

### 1.2 Stale Carry-Forward Balances at Year-End
* **Location:** 
  * [LeaveCalculationService.php](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Services/LeaveCalculationService.php#L17-L54) (in `initializeBalances`)
* **Problem:** 
  * Year-end rollover calculates carry-forward days using the previous year's remaining balance at the moment of initialization:
    `$carriedOver = $previousBalance->remaining_days;`
  * If a user's previous year's balance changes *after* the new year's balance has already been initialized (e.g., due to a retrospective leave cancellation or approval), the next year's carry-forward balance is never recalculated or updated.
* **Impact:** 
  * Employees will have incorrect leave allocations for the current year if their prior year's leave was adjusted after January 1st initialization.
* **Remediation:** 
  * Implement an event listener or observer on [LeaveBalance.php](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Models/LeaveBalance.php) that detects updates to `remaining_days` and automatically adjusts the next year's balance if `carry_forward` is enabled.

### 1.3 Config Caching Failures (`env()` Anti-pattern)
* **Location:** 
  * [LeaveRequestSubmitted.php](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Notifications/LeaveRequestSubmitted.php#L41-L43) (in `toMail`)
  * [LeaveStatusUpdated.php](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Notifications/LeaveStatusUpdated.php#L44-L46) (in `toMail`)
  * [AppServiceProvider.php](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Providers/AppServiceProvider.php#L27) (in `boot`)
* **Problem:** 
  * The application calls the `env()` helper directly outside configuration files (e.g., `env('APP_PROTOCOL')`, `env('APP_DOMAIN')`, `env('QUEUE_FLUSH_THRESHOLD')`).
* **Impact:** 
  * If configuration caching is enabled in production (`php artisan config:cache`), Laravel ignores `.env` files entirely and all `env()` calls outside config files return `null`. This breaks notification URLs (e.g., producing `:// /leaves` links) and disables queue flushing.
* **Remediation:** 
  * Move all environmental parameters into existing files under the `config/` directory (e.g., `config/app.php` and `config/queue.php`) and access them using the `config()` helper (e.g., `config('app.protocol')`).

### 1.4 Dead Code and Reflection Unit Tests
* **Location:** 
  * [ReportService.php](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Services/ReportService.php#L239-L252) (method `monthExtractionSql`)
  * [MonthlyStatsPortabilityTest.php](file:///home/hrutav-modha/Documents/sem5/sbtp/project/tests/Unit/MonthlyStatsPortabilityTest.php#L28-L90)
* **Problem:** 
  * `monthExtractionSql()` is a private method meant for generating database-portable month extractions. However, it is never called anywhere in the application logic. 
  * The unit test suite contains extensive reflection tests to verify its behavior for different drivers despite the method being dead code.
* **Impact:** 
  * Maintenance overhead and technical debt.
* **Remediation:** 
  * Remove the dead method and its corresponding unit tests, or utilize the method to optimize [ReportService.php](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Services/ReportService.php#L136-L174) database operations.

---

## 2. Efficiency Gaps

### 2.1 N+1 Query Database Performance Bottleneck in Reports
* **Location:** 
  * [ReportService.php](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Services/ReportService.php#L182-L226) (in `getWorkingDaysDistribution`)
* **Problem:** 
  * For every single leave request processed in `getEmployeeReport()` and `getDepartmentReport()`, `getWorkingDaysDistribution()` executes a database query to retrieve public holidays:
    ```php
    $publicHolidays = \App\Models\PublicHoliday::whereBetween('date', [
        $start->toDateString(),
        $end->toDateString()
    ])->pluck('date')->toArray();
    ```
* **Impact:** 
  * If an organization has 100 employees, each submitting 5 leave requests, loading the report page executes **500 additional database queries**. This causes severe database latency, high CPU usage, and slow page loads.
* **Remediation:** 
  * Fetch all public holidays for the current year/range in a single query at the start of the report generation, and pass that collection as a parameter to `getWorkingDaysDistribution()`.

### 2.2 CPU/Process Overhead via Dynamic Queue Worker Spawning
* **Location:** 
  * [AppServiceProvider.php](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Providers/AppServiceProvider.php#L26-L36)
* **Problem:** 
  * In the `JobQueued` event listener, the service provider executes a background shell command via PHP's `exec()` to start a queue worker:
    ```php
    $command = 'php ' . base_path('artisan') . ' queue:work --stop-when-empty > /dev/null 2>&1 &';
    exec($command);
    ```
* **Impact:** 
  * Running a shell command dynamically to spawn a PHP CLI runtime process for every queued job creates high CPU and RAM overhead. Under high concurrency, this will exhaust server resources, trigger process limits, or cause a denial of service (DoS).
* **Remediation:** 
  * Remove process execution from the application layer. Standard production practice is to run a persistent process manager like **Supervisor** or a system daemon to run queue workers continuously.

### 2.3 Local Development Redis Infrastructure Overhead
* **Location:** 
  * [.env](file:///home/hrutav-modha/Documents/sem5/sbtp/project/.env#L30-L49)
  * [run.sh](file:///home/hrutav-modha/Documents/sem5/sbtp/project/run.sh#L19-L21)
* **Problem:** 
  * The application defaults to using Redis for sessions, queue, and caching locally, forcing development scripts to build and spin up a local Redis server instance.
* **Impact:** 
  * If the local Redis instance crashes or is not started, the application fails to function.
* **Remediation:** 
  * For local development, change the default drivers in [.env.example](file:///home/hrutav-modha/Documents/sem5/sbtp/project/.env.example) to `database` or `file` so developers can run the system without a hard dependency on Redis.

---

## 3. Security Gaps

### 3.1 Unchecked Historical Leave Cancellation (Refund Exploit)
* **Location:** 
  * [LeaveController.php](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Http/Controllers/LeaveController.php#L122-L134) (in `cancel`)
* **Problem:** 
  * The `cancel` method allows an employee to cancel *any* leave request they own without checking the leave's date:
    ```php
    if ($leaveRequest->status === 'Approved') {
        $this->calculationService->refundBalance($leaveRequest);
    }
    ```
* **Impact:** 
  * Employees can cancel leave requests that took place in the past (e.g., weeks or months ago) and get their leave balance refunded dynamically. This allows users to exploit the system to gain infinite paid leave days.
* **Remediation:** 
  * Enforce that leave requests cannot be unilaterally cancelled by the employee once the start date has passed (`start_date <= today`). Past leaves should either be unmodifiable or require explicit manager/HR intervention.

### 3.2 Shell Command Injection Risks
* **Location:** 
  * [AppServiceProvider.php](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Providers/AppServiceProvider.php#L34)
* **Problem:** 
  * The app uses `exec()` to run shell commands within the web request context.
* **Impact:** 
  * Many security-hardened production environments disable shell execution functions via `disable_functions` in `php.ini`. More importantly, if `base_path()` or any other parameters could be influenced by external input, it would introduce a Remote Code Execution (RCE) vulnerability.
* **Remediation:** 
  * Eliminate the use of shell execution commands within application code. Rely on persistent background daemons for queue processing.

### 3.3 Lack of Concurrency Locking on Overlapping Leaves
* **Location:** 
  * [LeaveController.php](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Http/Controllers/LeaveController.php#L68-L72) (in `store`)
* **Problem:** 
  * Overlapping requests are checked using a simple database query:
    ```php
    $overlapExists = LeaveRequest::where('user_id', Auth::id())
        ->whereIn('status', ['Pending', 'Approved'])
        ->where('start_date', '<=', $request->end_date)
        ->where('end_date', '>=', $request->start_date)
        ->exists();
    ```
* **Impact:** 
  * There is a race condition. If an employee sends two identical requests simultaneously (e.g., via double-clicking the submit button or automated script), both requests will execute the check before either is written, allowing duplicate overlapping leaves to bypass validation and get saved.
* **Remediation:** 
  * Wrap the overlap check and save inside a database transaction and utilize a pessimistic or shared lock to serialize concurrent queries for the same user.
