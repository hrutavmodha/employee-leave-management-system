# Day 1: Project Setup and Environment Configuration

**Task to be Performed:** 
Initial project initialization, environment setup, and framework configuration for the Employee Leave Management System (ELMS).

**Activities / Tasks Actually Performed:**
- Initialized the Laravel 12 project structure and verified core directory layout.
- Configured the environment file (`.env`) for local development, selecting SQLite as the primary database connection.
- Reviewed the project's JavaScript dependencies in `package.json` and identified the requirement for front-end asset compilation via Vite.
- Verified the initial application state by checking the application's core service providers and routing configuration.
- Analyzed the project requirements and 15-day development plan provided by the company to map out the implementation roadmap.

**Tools Used:** 
- PHP 8.2.12
- Composer 2.x
- Laravel Framework 12.61.0
- Node.js v22.19.0 / npm 10.9.3
- SQLite
- VS Code
- Git

**Challenges faced and how was it addressed?**
- **Challenge:** Identified that `axios` was imported in `resources/js/bootstrap.js` but the `node_modules/` directory was absent, preventing client-side script execution.
- **Resolution:** Confirmed that `npm install` must be executed to materialize dependencies and that `npm run dev` is required for Vite's HMR (Hot Module Replacement).

**Skills / Knowledge Gained/Outcome:**
- Proficiency in Laravel 12 project bootstrapping and environment-specific configuration.
- Understanding of the latest Laravel directory structure and its Vite-based frontend tooling.
- Established a verified environment baseline for the upcoming database design phase.
