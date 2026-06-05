# Codebase Audit: Identified Bugs and Remediation Report

This document details the critical bugs, concurrency issues, architectural anti-patterns, and logic gaps identified in the Employee Leave Management System (ELMS) codebase.

---

## Summary of Findings

| Bug ID | Severity | Category | Location | Description |
| :--- | :--- | :--- | :--- | :--- |
| **BUG-01** | High | Concurrency / Data Integrity | `ApprovalController.php` | Side effects (notifications/emails) dispatched inside DB transactions. |
| **BUG-02** | High | Race Condition | `LeaveController.php` | Cancellation validations performed outside the locking transaction block. |
| **BUG-03** | Medium | Data Inconsistency | `LeaveRequest.php` | Static dates table (`leave_request_dates`) is not updated/cleared when request dates change. |
| **BUG-04** | Medium | Caching & Performance | `ReportService.php` | Thread-unsafe cache versioning and memory bloat from orphan keys. |
| **BUG-05** | Low | Data Corruption | `ProfileController.php` | Single-word profile name update deletes/empties the last name field in the database. |
| **BUG-06** | Low | Business Logic | `LeaveController.php` | Missing manager notification when an employee cancels a leave request. |
| **BUG-07** | Low | Code Quality / Exception Handling | `LeaveBalance.php` & `LeaveCalculationService.php` | Generic exceptions thrown instead of domain-specific exception types. |

---

## Detailed Analysis

### BUG-01: Non-Database Side Effects inside DB Transactions
* **Location:** [ApprovalController.php:L76](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Http/Controllers/ApprovalController.php#L76) & [ApprovalController.php:L115](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Http/Controllers/ApprovalController.php#L115)
* **Severity:** High
* **Description:** Both the `approve` and `reject` operations send user notifications (which can trigger SMTP mail transport actions) *inside* the database transaction blocks. 
* **Impact:** 
  1. **Transaction Failure / Performance Degradation:** If the notification service fails or experiences latency (e.g., SMTP timeout/exception), the entire database transaction is rolled back, keeping the leave request "Pending" even though the state change and balance deduction succeeded.
  2. **Ghost Side-Effects:** If the notification succeeds but the subsequent `DB::commit()` fails (due to database lock acquisition errors or other transaction constraints), the email is still sent, informing the employee of an action that was rolled back and never materialized.
* **Proposed Fix:** Move the `$lockedRequest->user->notify(...)` invocation outside of the `DB::transaction()` try-block. Perform notifications only after successful commit.

---

### BUG-02: Race Condition in Leave Request Cancellation
* **Location:** [LeaveController.php:L148-L152](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Http/Controllers/LeaveController.php#L148-L152)
* **Severity:** High
* **Description:** The date validation check (`$leaveRequest->start_date->lte($today)`) and the implicit state verification of the cancellation request are evaluated *before* entering the transaction block and applying `lockForUpdate()`.
* **Impact:** A race condition can occur if a user double-submits a cancellation request in rapid succession, or if the request status changes concurrently. Since the verification is done on a stale/non-locked model, it can pass the check, enter the transaction, and attempt to refund the balance multiple times, causing duplicate balance accruals (double-spending).
* **Proposed Fix:** Perform all state validations inside the transaction after retrieving the locked database record:
  ```php
  \Illuminate\Support\Facades\DB::transaction(function () use ($leaveRequest) {
      $lockedRequest = LeaveRequest::where('id', $leaveRequest->id)->lockForUpdate()->firstOrFail();

      if ($lockedRequest->start_date->lte(Carbon::today())) {
          throw new \Exception('You cannot cancel a leave request once the start date has started or passed.');
      }

      if ($lockedRequest->status === 'Approved') {
          $this->calculationService->refundBalance($lockedRequest);
      } elseif ($lockedRequest->status !== 'Pending') {
          throw new \Exception('Only pending or approved requests can be cancelled.');
      }

      $lockedRequest->update(['status' => 'Cancelled']);
  });
  ```

---

### BUG-03: Static Date Mutation Vulnerability on Update
* **Location:** [LeaveRequest.php:L85-L92](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Models/LeaveRequest.php#L85-L92)
* **Severity:** Medium
* **Description:** The statically persisted days in the `leave_request_dates` table are populated via the `created` hook when a `LeaveRequest` is created. However, there are no handlers/observers on the `updating` or `saved` events to update this table if the request's `start_date`, `end_date`, or `leave_type_id` is modified.
* **Impact:** If an administrator or program script modifies the dates of a leave request, the `leave_request_dates` table remains unchanged. This results in corrupt dashboard statistics, mismatched department statistics, and incorrect balance calculation refunds during cancellation since `refundBalance` reads from the outdated static dates table.
* **Proposed Fix:** Add an `updating` or `saved` hook to reconstruct the static date ranges if date attributes change, or enforce complete immutability on leave request dates once a record is created.

---

### BUG-04: Thread-Unsafe Cache Versioning & Memory Leak
* **Location:** [ReportService.php:L14-L23](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Services/ReportService.php#L14-L23)
* **Severity:** Medium
* **Description:** The `getEmployeeReport()` uses a two-tier caching strategy using a version number. If `reports.employees` key is absent, the version is cleared.
  ```php
  $hasCache = \Illuminate\Support\Facades\Cache::has('reports.employees');
  if (!$hasCache) {
      \Illuminate\Support\Facades\Cache::forget('reports.employees.version');
      \Illuminate\Support\Facades\Cache::put('reports.employees', true, 3600);
  }
  ```
* **Impact:** 
  1. **Race Condition:** Under concurrent requests, two threads may enter the `!$hasCache` block simultaneously. Thread A clears the version, Thread B checks and sees cache *is* present (put by Thread A), but the new version hasn't been written or is in the middle of writing, leading to caching errors.
  2. **Cache Key Bloat:** When model observers update/delete records, they only call `Cache::forget('reports.employees')`. The versioned cache keys (`reports.employees.v{version}...`) are never deleted and remain in storage, causing memory leaks/bloat in persistent cache drivers like Redis.
* **Proposed Fix:** Use atomic operations like cache tagging (`Cache::tags(...)`) where supported, or explicitly clean up version-specific keys on cache invalidation instead of relying on expiring timestamps.

---

### BUG-05: Profile Name Splitting / Last Name Deletion
* **Location:** [ProfileController.php:L32-L36](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Http/Controllers/ProfileController.php#L32-L36)
* **Severity:** Low
* **Description:** Profile name updates split the `name` string on the first space:
  ```php
  if ($request->has('name')) {
      $nameParts = explode(' ', $request->input('name'), 2);
      $user->first_name = $nameParts[0] ?? '';
      $user->last_name = $nameParts[1] ?? '';
  }
  ```
* **Impact:** If a user enters a single-word name (e.g. "Hrutav" or "Admin"), `$nameParts[1]` is undefined. This results in the database `last_name` field being overwritten with an empty string `""` silently.
* **Proposed Fix:** Check if a space exists before setting the last name, or validate that the `name` field contains both a first and last name (e.g., using regex validation or splitting constraints):
  ```php
  if ($request->has('name')) {
      $nameParts = explode(' ', trim($request->input('name')), 2);
      $user->first_name = $nameParts[0] ?? $user->first_name;
      if (isset($nameParts[1])) {
          $user->last_name = $nameParts[1];
      }
  }
  ```

---

### BUG-06: Missing Manager Notification on Cancellation
* **Location:** [LeaveController.php:L143-L172](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Http/Controllers/LeaveController.php#L143-L172)
* **Severity:** Low
* **Description:** While managers are notified when a new request is submitted (`LeaveRequestSubmitted`), they receive no notification when a request (which could be approved or pending) is cancelled by the employee.
* **Impact:** Communication gaps. Managers might plan work assuming a subordinate is on leave or returning, when the leave was actually cancelled.
* **Proposed Fix:** Implement a `LeaveRequestCancelled` notification and dispatch it to the user's manager when a leave cancellation completes successfully.

---

### BUG-07: Inconsistent Generic Exception Handling
* **Location:** [LeaveBalance.php:L54](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Models/LeaveBalance.php#L54), [LeaveCalculationService.php:L200](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Services/LeaveCalculationService.php#L200)
* **Severity:** Low
* **Description:** Domain-specific errors (such as insufficient balance or retrospective double-spending violation) throw the basic `\Exception` class.
* **Impact:** Fails to follow modern, production-grade API practices which recommend using specialized domain exception classes. This limits programmatic exception handling and leads to parsing raw exception messages in catch blocks.
* **Proposed Fix:** Create domain exception classes (e.g., `InsufficientLeaveBalanceException`) inheriting from a base domain exception class, making error handling more granular and robust.
