# Day 12: Leave Balance Calculation and Automation

**Task to be Performed:** 
Implement the "Leave Balance Module" with automatic deductions and initial balance allocations.

**Activities / Tasks Actually Performed:**
- Developed the `LeaveCalculationService` to centralize all leave-related mathematical logic.
- Implemented `initializeBalances` to automatically set up standard leave quotas for new employees upon registration.
- Developed `deductBalance` to accurately subtract approved leave days from the employee's available pool, with built-in checks for insufficient balance.
- Refactored `ApprovalController` to use database transactions and service-based deductions during the approval workflow.
- Updated `EmployeeController` to trigger automatic balance initialization for every newly created employee record.
- Verified balance tracking integrity (Allocated vs. Used vs. Remaining).

**Tools Used:** 
- PHP 8.2.12
- Laravel Framework 12.61.0
- Eloquent ORM
- Service Pattern Architecture
- VS Code

**Challenges faced and how was it addressed?**
- **Challenge:** Preventing "phantom" deductions if the status update fails after the balance is subtracted.
- **Resolution:** Wrapped the deduction and status update in a `DB::transaction` block to ensure both happen together or not at all (atomicity).

**Skills / Knowledge Gained/Outcome:**
- Mastery of the Service Pattern in Laravel for decoupling business logic from controllers.
- Practical implementation of database transactions for critical financial/numeric data integrity.
- Successfully automated the most complex logical component of the ELMS system.
