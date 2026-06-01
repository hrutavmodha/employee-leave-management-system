# Day 4: Finalizing User Roles and Core Architecture

**Task to be Performed:** 
Establish core Eloquent Models, define relational mapping, and implement Role-Based Access Control (RBAC) middleware.

**Activities / Tasks Actually Performed:**
- Created core Eloquent Models: `Department`, `LeaveType`, `LeaveBalance`, and `LeaveRequest` as per Page 10 of requirements.
- Defined all one-to-many and belongs-to relationships to mirror the company's Entity Relationship Diagram.
- Extended the `User` model with helper methods (`isAdmin()`, `isManager()`, `isEmployee()`) for cleaner logic.
- Implemented `CheckRole` middleware to protect routes based on the three defined roles (Employee, Manager, HR/Admin).
- Registered the `role` middleware alias in `bootstrap/app.php` for project-wide availability.

**Tools Used:** 
- PHP 8.2.12
- Laravel Framework 12.61.0
- Eloquent ORM
- Artisan CLI
- VS Code

**Challenges faced and how was it addressed?**
- **Challenge:** Mapping complex self-referencing relationships (Users as Managers of other Users).
- **Resolution:** Implemented a self-referencing `manager_id` foreign key with a `subordinates()` has-many relationship on the `User` model.

**Skills / Knowledge Gained/Outcome:**
- Proficiency in Eloquent relational mapping and advanced model configuration.
- Practical implementation of custom Middleware in Laravel 12.
- Foundation established for **Day 5-6: Employee Management**.
