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
   - **Run the Commands:**
     ```bash
     cp .env.example .env
     php artisan key:generate
     ```
     These commands will duplicate the template environment configuration file (`.env.example`) to create your local active environment configuration file (`.env`), and then generate a secure, unique 32-character application encryption key (`APP_KEY`) to encrypt user sessions and other sensitive data.
   - **Redis Configuration:** Update the following keys in your `.env` file to utilize the local Redis server for session management, queueing, and caching:
     ```env
     SESSION_DRIVER=redis
     QUEUE_CONNECTION=redis
     CACHE_STORE=redis
     REDIS_CLIENT=predis
     REDIS_HOST=127.0.0.1
     REDIS_PORT=6379
     ```
   - To receive actual notifications, set `MAIL_HOST=smtp.gmail.com`, `MAIL_PORT=465`, and `MAIL_ENCRYPTION=ssl` in your `.env` file using a Google App Password.

> [!NOTE]
> **Why a Local Redis Build?**
> Rather than relying on a globally installed `redis-server` binary, this repository ships with a compiled local Redis build under `redis-local/`. This approach ensures:
> 1. **Zero-Dependency Portability:** No need for system-level installation via `apt`, `brew`, or other package managers, which typically require root/sudo access.
> 2. **No Port/Configuration Conflicts:** Isolates the project database/cache from any other Redis instances running on the host machine.
> 3. **No C-Extension Requirements:** Setting `REDIS_CLIENT=predis` makes Laravel use the bundled `predis/predis` Composer library. This avoids the requirement of compiling/installing the native PHP `phpredis` C-extension on the host machine.

4. **Database Initialization:**
   ```bash
   touch database/database.sqlite
   php artisan migrate --seed
   ```

5. **Run the Application:**
   ```bash
   ./run.sh
   ```
   * Automatically starts Backend & Frontend servers and opens the application in your default browser at the local network IP for responsiveness testing.

---

## &#x1F511; Default Credentials (Development)

The system comes pre-seeded with an Administrative account for testing:

- **Email:** `test@example.com`
- **Password:** `password`
- **Role:** HR/Admin
- **Department:** Human Resources
