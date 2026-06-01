# Day 3: Authentication and User Roles

**Task to be Performed:** 
Implement user authentication (Login, Logout, Registration) and set up the foundation for Role-Based Access Control (RBAC).

**Activities / Tasks Actually Performed:**
- Installed and configured **Laravel Breeze** with the **Blade** stack for server-side rendering.
- Successfully scaffolded authentication controllers, routes, and views.
- Compiled frontend assets (Tailwind CSS, Vite) to support the new authentication UI.
- Verified 23 new routes, including `login`, `register`, `password-reset`, and `profile` management.
- Initialized the `dashboard` route as the protected landing page for authenticated users.

**Tools Used:** 
- PHP 8.2.12
- Laravel Framework 12.61.0
- Composer
- Node.js / npm
- Laravel Breeze
- Tailwind CSS / Vite

**Challenges faced and how was it addressed?**
- **Challenge:** Reconciling the manual database schema from Day 2 with the automated scaffolding of Breeze.
- **Resolution:** Used the `--no-interaction` flag during Breeze installation to prevent overwriting custom database configurations while ensuring all auth views were correctly mapped.

**Skills / Knowledge Gained/Outcome:**
- Understanding of Laravel's starter kits and the "Blade" templating engine.
- Experience with Vite asset bundling in a Laravel 12 environment.
- Fully functional authentication system ready for Role-Based Access Control (RBAC) integration.
