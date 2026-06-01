# Day 14: Automated Email Notifications

**Task to be Performed:** 
Implement "Email Notifications" to inform employees of leave request status updates as per project specifications.

**Activities / Tasks Actually Performed:**
- Created the `LeaveStatusUpdated` Laravel Notification class to handle automated email generation.
- Designed a professional email template containing leave type, duration, approval status, and manager comments.
- Integrated the notification trigger into the `ApprovalController` for both `approve` and `reject` actions.
- Configured the system to use the `log` mail driver for local development verification.
- Verified that notifications are correctly dispatched to the specific employee who submitted the request.

**Tools Used:** 
- PHP 8.2.12
- Laravel Framework 12.61.0
- Laravel Notifications
- Mailables
- VS Code

**Challenges faced and how was it addressed?**
- **Challenge:** Creating a unified notification that handles different statuses (Approved vs. Rejected) dynamically.
- **Resolution:** Developed a parameterized constructor in the Notification class to accept the `LeaveRequest` model and extract current state data at runtime.

**Skills / Knowledge Gained/Outcome:**
- Proficiency in Laravel's Notification system and Mail message formatting.
- Understanding of automated communication workflows in business applications.
- Successfully completed the "Email Notifications" block of the development plan.
