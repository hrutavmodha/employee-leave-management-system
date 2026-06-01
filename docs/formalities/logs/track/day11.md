# Day 11: Leave Approval Workflow Implementation

**Task to be Performed:** 
Implement the "Leave Approval Module" for Managers and HR/Admins to review, approve, or reject employee leave requests.

**Activities / Tasks Actually Performed:**
- Developed the `ApprovalController` with role-based filtering logic (Managers see subordinates; Admins see all).
- Implemented `approve` and `reject` methods with support for mandatory manager comments during rejection.
- Updated the `CheckRole` middleware to support multiple role parameters (e.g., `role:Manager,HR/Admin`).
- Created the `approvals.index` Blade view featuring a streamlined review table and inline processing forms.
- Configured secure approval routes and integrated "Pending Approvals" into the global navigation for supervisors.
- Implemented JavaScript-based form submission logic to handle multi-action processing from a single view.

**Tools Used:** 
- PHP 8.2.12
- Laravel Framework 12.61.0
- Eloquent ORM
- Blade Templating Engine
- Tailwind CSS
- VS Code

**Challenges faced and how was it addressed?**
- **Challenge:** Ensuring managers only see requests from their own direct reports to maintain organizational privacy.
- **Resolution:** Utilized Laravel's `whereHas` Eloquent method to perform a relational query check against the `manager_id` field.

**Skills / Knowledge Gained/Outcome:**
- Proficiency in complex relational querying and cross-model validation.
- Experience in building multi-role management dashboards with conditional UI elements.
- Successfully completed the "Approval Workflow" block of the development plan.
