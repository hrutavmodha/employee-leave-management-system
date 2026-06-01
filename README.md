# Employee Leave Management System (ELMS)

A robust, enterprise-grade web application built with **Laravel 12** to automate the employee leave request and approval workflow. This project replaces manual, spreadsheet-based tracking with a digital, role-based system featuring real-time balance calculations and automated notifications.

---

## 🚀 Key Features

### 1. Authentication & Role-Based Access Control (RBAC)
- **Multi-Role Support:** Distinct interfaces and permissions for **Employees**, **Managers**, and **HR/Admins**.
- **Secure Access:** Built-in protection against unauthorized actions via custom Middleware.
- **Privacy Focused:** Minimal data exposure on public UI elements (e.g., masked emails in navigation).

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
- **One-Click Actions:** Instant approval or rejection with mandatory feedback comments.
- **Atomic Operations:** Database transactions ensure leave balances are only deducted upon successful approval.

### 5. Leave Balances & Automation
- **Dynamic Quotas:** HR can define leave types (Annual, Sick, WFH, etc.) with custom "Carry Forward" rules.
- **Automatic Deductions:** Intelligent service layer handles all mathematical operations, preventing manual entry errors or "Insufficient Balance" overdraws.

### 6. Reports & Analytics
- **Visual Dashboard:** Aggregated data on total leaves taken, department-wise statistics, and monthly approval trends.
- **Employee Summaries:** At-a-glance view of all staff balances and historical usage.

---

## 🛠️ Technical Stack

- **Backend:** PHP 8.2+ / Laravel 12 (latest)
- **Frontend:** Blade Templating Engine / Tailwind CSS / Alpine.js
- **Database:** SQLite (Single-file portability)
- **Asset Bundling:** Vite 7.x
- **Architecture:** Service Pattern (Calculation & Reporting layers)
- **Tooling:** Artisan CLI / Composer / npm

---

## 📦 Installation & Setup

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
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database Initialization:**
   ```bash
   touch database/database.sqlite
   php artisan migrate --seed
   ```

5. **Run the Application:**
   *Terminal 1 (Backend):*
   ```bash
   php artisan serve
   ```
   *Terminal 2 (Frontend):*
   ```bash
   npm run dev
   ```

---

## 🔑 Default Credentials (Development)

The system comes pre-seeded with an Administrative account for testing:

- **Email:** `test@example.com`
- **Password:** `password`
- **Role:** HR/Admin
