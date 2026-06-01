# Day 2: Database Design and Core Schema Implementation

**Task to be Performed:** 
Design and implement the relational database schema for the Employee Leave Management System (ELMS) as per company requirements.

**Activities / Tasks Actually Performed:**
- Conducted a detailed analysis of the `requirements.pdf` to extract the complete database schema and entity relationships.
- Mapped the required fields for `users`, `departments`, `leave_types`, `leave_balances`, `leave_requests`, and `attachments`.
- Planned the migration sequence to ensure all foreign key constraints (e.g., `department_id`, `manager_id`, `leave_type_id`) are satisfied.
- Verified the existing Laravel 12 migration state and prepared to extend the `users` table for Role-Based Access Control (RBAC).

**Tools Used:** 
- PHP 8.2.12
- Laravel Framework 12.61.0
- Artisan CLI
- SQLite
- VS Code

**Challenges faced and how was it addressed?**
- **Challenge:** Ensuring the organizational hierarchy (Employee -> Manager) is correctly represented in the `users` table schema.
- **Resolution:** Decided to implement a self-referencing `manager_id` foreign key on the `users` table and a `role` enum to handle permissions.

**Skills / Knowledge Gained/Outcome:**
- Mastery of Laravel migration workflows and relational database design.
- Practical experience in translating business requirements into a technical database schema.
- Established the data foundation for the ELMS application.
