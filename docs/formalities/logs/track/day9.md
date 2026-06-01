# Day 9: Leave Request Module Implementation

**Task to be Performed:** 
Develop the "Leave Request Module" to allow employees to submit leave applications with automatic day calculation.

**Activities / Tasks Actually Performed:**
- Created the `LeaveController` to manage leave application workflows.
- Implemented the `leaves.index` view to display personal leave history with real-time status updates.
- Developed the `leaves.create` view with a comprehensive application form including file attachment support.
- Integrated `Carbon` date logic in the controller to automatically calculate the total number of requested days based on start and end dates.
- Established the `Attachment` model and linked it to `LeaveRequest` for supporting document management.
- Configured routes and updated the global navigation to include "My Leaves" for all authenticated users.

**Tools Used:** 
- PHP 8.2.12
- Laravel Framework 12.61.0
- Carbon Library
- Eloquent ORM
- Blade Templating Engine
- VS Code

**Challenges faced and how was it addressed?**
- **Challenge:** Ensuring the system correctly calculates inclusive days (e.g., June 1 to June 1 should be 1 day).
- **Resolution:** Implemented `$start->diffInDays($end) + 1` logic to ensure the requested duration is mathematically accurate for HR purposes.

**Skills / Knowledge Gained/Outcome:**
- Mastery of date manipulation and mathematical logic within Laravel controllers.
- Understanding of file upload handling and relational data storage for attachments.
- Successfully implemented the core employee-facing functionality of the leave system.
