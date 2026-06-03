# Employee Leave Management System (ELMS)

A robust, enterprise-grade web application built with **Laravel 12** to automate the employee leave request and approval workflow. This project replaces manual, spreadsheet-based tracking with a digital, role-based system featuring real-time balance calculations and automated notifications.

---

## &#x1F680; Key Features

### 1. Authentication & Role-Based Access Control (RBAC)
- **Multi-Role Support:** Distinct interfaces and permissions for **Employees**, **Managers**, and **HR/Admins**.
- **Secure Access:** Built-in protection against unauthorized actions via custom Middleware.
- **Theme Support:** Fully integrated **Light/Dark Mode** with persistent user preference and system-sync.
- **Privacy Focused:** Minimal data exposure on public UI elements (e.g., role-based navbar masking).

### 2. Employee Management
- **Organizational Hierarchy:** Manage departments and reporting structures (Manager-Subordinate relationships).
- **Comprehensive Profiles:** Track designations, joining dates, and employment status.
- **Automated Provisioning:** Newly created employees automatically receive initialized leave balances based on active policies.

### 3. Leave Request Workflow
- **Application Engine:** Employees can apply for leave with automatic duration calculation using `Carbon`.
- **Supporting Documents:** Support for file attachments (PDF, JPG, PNG) for medical or official evidence.
- **Interactive Tracking:** Real-time status updates (Pending, Approved, Rejected, Cancelled).

### 4. Managerial Approval System
- **Supervisor Dashboard:** Managers see pending requests from their direct reports; Admins see organizational-wide requests.
- **One-Click Actions:** Instant approval or rejection with optional feedback comments.
- **Atomic Operations:** Database transactions ensure leave balances are only deducted upon successful approval.

### 5. Leave Balances & Automation
- **Dynamic Quotas:** HR can define leave types (Annual, Sick, WFH, etc.) with custom "Carry Forward" rules.
- **Automatic Deductions:** Intelligent service layer handles all mathematical operations, preventing manual entry errors or "Insufficient Balance" overdraws.

### 6. Reports & Analytics
- **Visual Dashboard:** Aggregated approved leaves, department-wise statistics, and an interactive monthly approved leaves vertical bar chart.
- **Employee Summaries:** At-a-glance view of all staff balances, departments, and historical usage.

---

## &#x1F6E0;&#xFE0F; Technical Stack

- **Backend:** PHP 8.2+ / Laravel 12 (latest)
- **Frontend:** Blade Templating Engine / Tailwind CSS / Alpine.js
- **Database:** SQLite (Single-file portability)
- **Asset Bundling:** Vite 7.x
- **Architecture:** Service Pattern (Calculation & Reporting layers)
- **Tooling:** Artisan CLI / Composer / npm

---

## &#x1F4E6; Installation & Setup

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js & npm

### Steps
1. **Clone the repository:**
   ```bash
   git clone https://github.com/hrutavmodha/employee-leave-management-system
   cd employee-leave-management-system
   ```

2. **Install Dependencies:**
   ```bash
   composer install
   npm install
   ```

3. **Environment Setup:**
   - Copy `.env.example` to `.env` and run `php artisan key:generate`.
   - **Real Emails:** To receive actual notifications, set `MAIL_HOST=smtp.gmail.com`, `MAIL_PORT=465`, and `MAIL_ENCRYPTION=ssl` in your `.env` file using a Google App Password.

4. **Database Initialization:**
   ```bash
   touch database/database.sqlite
   php artisan migrate --seed
   ```

5. **Run the Application:**
   ```bash
   ./run.sh
   ```
   *(Automatically starts Backend & Frontend servers and opens the application in your default browser at the local network IP for responsiveness testing.)*

---

## &#x1F511; Default Credentials (Development)

The system comes pre-seeded with an Administrative account for testing:

- **Email:** `test@example.com`
- **Password:** `password`
- **Role:** HR/Admin
- **Department:** Human Resources
