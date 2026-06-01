# Day 13: Leave Reports and Analytics Implementation

**Task to be Performed:** 
Implement the "Reports Module" to provide organizational insights into leave trends, department statistics, and employee balances.

**Activities / Tasks Actually Performed:**
- Developed the `ReportService` to aggregate complex data sets, including employee-specific summaries and department-wide metrics.
- Implemented `getMonthlyStats` using SQLite-compatible date extraction to visualize approval trends over the current year.
- Developed the `ReportController` to coordinate data delivery between the service layer and the front-end dashboard.
- Created the `reports.index` Blade view with multiple analytical sections: Employee Summary, Department Overview, and a visual Monthly Trend tracker.
- Integrated current leave balance tracking directly into the report table for real-time visibility.
- Configured administrative routes and navigation links for the Reporting suite.

**Tools Used:** 
- PHP 8.2.12
- Laravel Framework 12.61.0
- SQLite
- Service Pattern Architecture
- Blade & Tailwind CSS
- VS Code

**Challenges faced and how was it addressed?**
- **Challenge:** Efficiently querying and grouping data by month in a cross-platform compatible way (SQLite vs. MySQL).
- **Resolution:** Utilized `strftime('%m', start_date)` in the raw query to ensure accurate monthly grouping within the local development environment.

**Skills / Knowledge Gained/Outcome:**
- Proficiency in advanced data aggregation and grouping using Eloquent and Raw SQL.
- Experience in building comprehensive analytical dashboards for business users.
- Successfully completed the "Reports" block of the development plan.
