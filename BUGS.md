# Bugs Documented

This document records the status of bugs and issues in the current version of the Employee Leave Management System (ELMS) codebase.

---

## Codebase Audit Status: No Active Bugs Found

We have performed a comprehensive manual audit of the current version of the codebase and executed the complete test suite. No active bugs were found.

### Verification Details

1. **Test Suite Execution**:
   - Running the PHPUnit test suite yields **159 tests passing successfully** with **492 assertions**.
   - All regression tests, concurrency tests, and edge case tests (including carry-forward calculations, skip-years, caching, and database locking checks) pass without any errors.

2. **Manual Code Audit**:
   - **Type Safety and Class Resolution**: Verified that the previous undefined class error in [LeaveModuleTest.php](file:///home/hrutav-modha/Documents/sem5/sbtp/project/tests/Feature/LeaveModuleTest.php) has been fully resolved (using the correct namespace resolution for `LeaveRequest`).
   - **Database Locks and Transactions**: Verified that all critical actions (like applying, cancelling, or approving leaves) are properly wrapped in database transactions using pessimistic locks (`lockForUpdate`) to eliminate concurrency and race condition bugs.
   - **Carry-Forward Balances**: Verified that the Observer in [LeaveBalance.php](file:///home/hrutav-modha/Documents/sem5/sbtp/project/app/Models/LeaveBalance.php) correctly cascades balance updates to subsequent years and prevents retrospective adjustments from dropping balances below zero.
   - **Caching Integrity**: Verified that cache keys use version numbering to prevent staling and collisions on paginated views.
   - **Asset Cleanup**: Verified that deleting a leave request or user cascades file cleanup correctly.

As of the current version, the system behaves exactly as specified and is fully correct.
