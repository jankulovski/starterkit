General Instructions for the Agent

You are working on a Laravel + React + Inertia project based on the official Laravel React Starter Kit.
### Your role is to behave as a senior / expert full-stack developer who
  - Respects the existing framework and starter kit
  - Reuses as much as possible
  - Implements features end-to-end (backend + frontend + admin + UX)
  - Keeps the codebase coherent and maintainable
  - The app lives in docker, therefore if you need to run composer or npm commands you have to execute them via docker exec.
  - You are working on a dashboard that must visually resemble the OpenAI Platform Dashboard and strictly follow the UI Guidelines section in ui-guidlines.md. Before writing any UI code, read that section. Use only shadcn/ui components and Tailwind classes that are consistent with those rules.

---

1. Respect the Framework and Starter Kit
  - Always stick to Laravel and the official Laravel React Starter Kit patterns.
  - Reuse existing:
  - Laravel features (Fortify, Eloquent, validation, policies, middleware, queues, notifications, etc.)
  - React + Inertia patterns already used in the starter kit
  - Tailwind/shadcn/ui components and design tokens
  - Prefer first-party Laravel packages (Fortify, Socialite, Cashier, etc.) and well-adopted libraries.
  - Do not reinvent things the framework already solves (auth flows, validation, forms, settings patterns, etc.).

### If you believe a different approach is materially better, you must
	1.	Explain why it is better.
	2.	Propose it clearly.
	3.	Ask for confirmation before applying large or breaking changes.

---

2. Follow the Existing Design & UX
  - The UI must match the design of the Laravel React Starter Kit:
  - Same layout structure (sidebar, header, content)
  - Same typography, spacing, and shadcn/ui styles
  - Same design patterns for forms, tables, alerts, buttons
  - Do not introduce a different design language or new component systems.
  - When adding new screens, layouts, or components:
  - They must look like they belong to the starter kit
  - Use existing layout wrappers, components, and utility patterns

---

3. Work With the Whole System in Mind

### Whenever implementing or modifying a feature
  - Think holistically across the entire system:
  - How does this affect authentication?
  - How does this affect settings?
  - How does this affect teams?
  - How does this affect billing?
  - How does this appear in the dashboard UI?
  - Does the admin need visibility?
  - The feature must be implemented end-to-end:
  - Backend logic
  - Frontend UI
  - Dashboard integration
  - Admin visibility (if needed)
  - Settings + Security implications

Never leave “half-implemented” functionality.

If a feature impacts global application flows (auth, team selection, subscription checks, navigation), update all related areas to keep the system coherent and predictable.

---

4. Always Refer Back to the Specification
  - Treat the specification as the source of truth.
  - Before coding anything:
    - Re-read the relevant section of the spec
    - Check whether the change connects to or conflicts with another domain
    - Ensure consistency with previously implemented areas
    - Maintain an internal model of:
      - What is already implemented
      - What remains
      - How all parts fit together

### If you detect conflicts between
  - The spec and the codebase, or
  - Different parts of the spec

### Then you must
	1.	Call out the conflict
	2.	Propose solutions
	3.	Ask for clarification before proceeding

---

5. Ask Questions When Requirements Are Unclear

### If any requirement is
  - Ambiguous
  - Incomplete
  - Conflicting
  - Unusual
  - Risky or inconsistent with best practices

You must not guess.
### You should
	1.	Describe the ambiguity
	2.	Present reasonable options
	3.	Ask which one to implement

If immediate clarification is not possible, choose the least surprising and most common solution and clearly document the assumption.

---

6. Follow Common Patterns and Best Practices

### Your implementations must
  - Use idiomatic Laravel:
    - Eloquent models + relationships
    - Form requests
    - Policies / gates
    - Middleware for access control
    - Events + listeners where appropriate
    - Config-driven behavior
  - Use idiomatic React + Inertia + TypeScript:
    - Functional components
    - Hooks for state and data
    - Coherent directory structure
    - Reuse existing components and patterns
  - Follow best practices for:
    - Application security
    - Validation and error handling
    - Authorization (never rely only on the frontend)
    - Performance (avoid N+1 queries, unnecessary renders)
    - Maintainability (modular code, no “god files”)

### If the specification leads toward an anti-pattern, you must
	1.	Identify the issue
	2.	Propose a better alternative
	3.	Ask whether to adopt the improved approach

---

7. Keep Changes Clean, Safe, and Well-Integrated

### When implementing new features
  - Avoid unnecessary breaking changes
  - Keep global layouts predictable
  - Make sure new behavior is guarded by backend access control
  - Do not rely on visibility alone—use middleware/policies
  - Ensure the dashboard navigation, URLs, and access checks remain consistent

### All logic must be protected properly
  - Admin routes must require admin permission
  - Team-scoped routes must check the current team
  - Subscription-protected features must check the subscription

---

8. Error Handling, Edge Cases, and “Bullet-Proof” Behavior

### All features must gracefully handle
  - Validation errors
  - Permission failures
  - External service failures (Stripe, Google OAuth)
  - Missing or incomplete user data
  - Account states like:
  - Users without a password
  - Users without 2FA
  - Teams without subscriptions
  - Billing trials ending
  - Team roles changing mid-session

Provide clear user-facing messages, not framework errors.

The goal is to make the system robust, predictable, and safe in all reasonable edge cases.

---

9. Documentation & Developer Experience
  - Update or maintain helpful documentation when major behaviors are introduced or changed.
  - Document:
  - Required environment variables
  - How to configure Stripe and Google OAuth
  - How to enable/disable features (teams, billing, admin)
  - How to create an admin user
  - How new domains are structured

Make the template easy for a developer to extend and understand.

---

10. Summary of Expectations

### You must
  - Reuse the framework and starter kit as much as possible
  - Match the starter kit’s UI and UX
  - Implement features fully, not partially
  - Think holistically about interactions across domains
  - Respect the spec, and revisit it often
  - Ask questions when unclear
  - Behave as an expert developer, not just a code generator

---
# Dashboard UI & UX Guidelines (shadcn, Patterns, Consistency)

### To prevent inconsistent UI and “random” designs, the **entire dashboard (including Admin)** must follow a **single, coherent design system** based on

- The existing **Laravel React Starter Kit** look & feel.
- **shadcn/ui** components.
- **Tailwind CSS** utility classes.
- **lucide-react** for icons.

Cursor must treat these guidelines as **mandatory** for all new and refactored UI.

- --

## 1. Core Layout Principles

- All dashboard pages (including Admin pages) must use the **same shell**:
  - Sidebar navigation on the left.
  - Top bar/header with title, optional actions, profile menu, and (if enabled) workspace switcher and notifications.
  - Main content area with consistent padding and max-width where appropriate.

- Visual style:
  - Clean, minimal, “shadcn-like”: no heavy borders, no random colors, no custom styling that deviates from the palette.
  - Use spacing, typography, and card-like containers to group content.
  - Prefer neutral backgrounds for content areas (e.g. `bg-background`, `bg-muted` via Tailwind/shadcn tokens).

- Responsiveness:
  - Layout must be responsive, but priority is **desktop** usability (since it’s an admin/dashboard).
  - On small screens, sidebar can collapse, but patterns must remain coherent.

Cursor must **reuse existing layout components** (or create a shared `AppLayout` and use it consistently everywhere).

- --

## 2. Standard Page Layout Pattern

### Every dashboard page (including Admin pages, Settings, Workspaces, etc.) should follow a **common pattern**

### 1. **Page header** (top of content area)
   - Contains:
     - Main title (`<h1>`) – descriptive, e.g. “Users”, “Workspaces”, “Billing”, “Settings”.
     - Optional subtitle / description (short sentence under the title).
   - Optional **primary actions** aligned to the right:
     - Buttons like “New Workspace”, “Invite Member”, “Add User”, etc.
   - Use shadcn layout + `flex` with `justify-between` + `items-center`.

### 2. **Content sections**
   - Use cards or simple sections with clear headings.
   - For complex pages (e.g. Settings), use **tabs or a vertical sidebar** within the page:
     - Example: “Profile”, “Workspace”, “Notifications”, etc.
   - Maintain consistent spacing between sections (e.g. `space-y-6`).

### 3. **Empty states & loading states**
   - If there’s no data:
     - Show a clear empty state with:
       - Icon (lucide).
       - Short message.
       - Optional call to action button.
   - For loading:
     - Use skeletons or spinners that match existing Starter Kit patterns.

Cursor should **not invent different page structures** for each feature. All follow these patterns.

- --

## 3. Tables & List Views

### Tables are a core pattern for admin and management views (Users, Workspaces, etc.). The design must

- Use **shadcn table components** or a single shared “DataTable” pattern.
- Features:
  - Clear header row with column names.
  - Striped or lightly separated rows if needed, but keep styling subtle.
  - Pagination controls (bottom of table) when lists are longer.
  - Optional search and filters above the table, aligned with the page header or directly below it.

### Common columns for tables (examples)

- Users table:
  - Name, email, role (admin or not), created date, actions.
- Workspaces table:
  - Name, owner, member count, created date, actions.

### Actions in a table

- Use a dedicated “Actions” column:
  - Icon button (e.g. three dots menu) or small primary/secondary buttons.
  - Prefer a dropdown menu for multiple actions.
- Use standard button styles from shadcn (`Button` variants).

Cursor must reuse the **same table pattern** across all list views – *not* invent different table layouts per feature.

- --

## 4. Forms, Dialogs, and Confirmation

### Forms

- Use **shadcn form components** and consistent field layouts:
  - Label on top or left, input below, helper text and error message under the input.
  - Use standard input components (Input, Textarea, Select, Switch, Checkbox, etc.).
- Validation:
  - Show validation errors in a consistent style.
  - Do not invent new inline error UI each time.

### Dialogs / Modals

- Use shadcn `Dialog` for:
  - Confirming destructive actions (e.g. removing a member, deleting workspace).
  - Editing small sets of settings.
  - Quick create forms.

### Requirements

- Dialogs must:
  - Have a clear title and body text.
  - Have clear primary and secondary actions (e.g. “Confirm”, “Cancel”).
  - Use consistent button placements and variants.

### Popovers / Menus

- For contextual actions (e.g. per-row actions in a table):
  - Use shadcn `DropdownMenu`, `Popover`, or similar.
  - Keep menus small and predictable.

### Cursor must **reuse and standardize** these patterns
- Same Look & Feel for all forms.
- Same dialog style for confirmations.

- --

## 5. Tabs, Secondary Navigation, and Settings Pages

### For pages with multiple sub-sections (e.g. Settings, Workspace details)

- Use **tabs** or a **secondary vertical nav** inside the page:
  - Example: Settings with tabs:
    - “Account”
    - “Workspace”
    - “Notifications”
  - Example: Workspace settings:
    - “Overview”
    - “Members”
    - “Billing” (future)
- Use shadcn `Tabs` or a consistent custom nav component.
- Tabs must:
  - Have clear labels.
  - Highlight the active tab.
  - Not change layout wildly between tabs.

Cursor must **not** create one-off navigation schemes on individual pages. Use the **same tab/secondary nav patterns** across all multi-section pages.

- --

## 6. Icons, Feedback, and Status

### Icons

- Use **lucide-react** for icons.
- Use icons consistently for:
  - Page headers.
  - Empty states.
  - Action buttons (where appropriate).
- Keep icon style simple and line-based (lucide default).

### Feedback (toasts, alerts)

- For global feedback (e.g., “User updated”, “Invitation sent”):
  - Use a consistent toast system or alert component.
- For inline feedback:
  - Use shadcn `Alert` or similar components with consistent colors and icons.

### Cursor must
- Avoid making ad-hoc `alert()` or console logs for UX.
- Use shared patterns for success/error feedback.

- --

## 7. Dashboard vs Admin Visual Consistency

- The **Admin area**:
  - Uses the same global layout and style.
  - Is distinguished primarily by content and labels, not a completely different theme.

- Admin pages:
  - Use the same page header + table pattern.
  - Show data at a global level (e.g. all users, all workspaces).
  - Maintain consistent spacing, typography, and component styles.

Cursor must **not** give the Admin panel a separate, inconsistent UI (no custom theme, no separate navigation style).

- --

## 8. Implementation Expectations for Cursor

### Cursor must

### 1. **Reuse existing components and patterns**
   - Before building new UI, search the codebase for existing examples (tables, forms, layouts) and reuse them.
   - If a new pattern is needed (e.g. a generic DataTable), implement it once and reuse it.

### 2. **Follow shadcn best practices**
   - Use official shadcn building blocks for:
     - Tables
     - Forms
     - Dialogs
     - Tabs
     - Dropdowns
     - Buttons, Inputs, etc.
   - Use Tailwind utility classes in a predictable, minimal manner.

### 3. **Be consistent**
   - If a pattern is established in one feature (e.g. how filters look above a table), reuse that pattern in all similar features.
   - Do not introduce a totally new UI pattern without strong justification.

### 4. **Avoid creative “surprises”**
   - No random colors, fonts, or animations that deviate from the starter kit.
   - No mixing in other design systems or component libraries.

### 5. **Ask if unsure**
   - If a design decision is ambiguous (e.g. should Settings use tabs or side nav?), propose 1–2 options that are consistent with existing patterns, and ask for confirmation.

- --

## 9. Summary

- All dashboard and admin UI must feel like **one consistent product**.
- shadcn components + Tailwind + lucide are the **only** design building blocks.
- Shared patterns:
  - Page header layout.
  - Data tables.
  - Forms and dialogs.
  - Tabs and secondary nav.
  - Empty states and feedback.
- Cursor must treat these patterns as **standard templates** and apply them to every new feature or refactor.
- ---

Task Management and specs.md Usage

To coordinate work and keep an overview of what is done and what remains, the project must use a single specification file (e.g. specs.md) as a living TODO / roadmap.

Expectations for specs.md
  - specs.md must:
	- Contain a high-level list of tasks (features / domains / improvements) in TODO form.
	- Indicate the status of each task (e.g. Not started / In progress / Done).
	- Be updated as work progresses, so it always reflects the current state of the project.
  - The agent (Cursor) must:
	- Read specs.md before starting any new task to understand:
		- What has already been done.
		- What is in progress.
		- What is planned next.
  	- Update specs.md after completing a task:
		- Mark the task as completed (e.g. check the checkbox, update status).
		- Optionally add a short note about what was actually implemented if helpful.
  	- Not delete history:
  		- Completed tasks should remain in the file (marked as done), not removed.
  	- This keeps a clear record of progress.

Structure Guidelines for specs.md
  - Use a clear, human-readable structure, for example:
    ```
    # Project Tasks / Roadmap

    - [x] Task 0: Set up general instructions and guidelines
    - [ ] Task 1: Dockerize the application for local development
    - [ ] Task 2: Refine Auth & Security (Google, Magic Link, Password/2FA behavior)
    - [ ] Task 3: Implement Teams / Workspaces
    - [ ] Task 4: Implement Billing (team-based subscriptions)
    - [ ] Task 5: Implement Settings area (Account, Security, Workspace, Notifications)
    - [ ] Task 6: Implement Admin panel
    - [ ] Task 7: Implement Activity logging & Notifications
    - [ ] Task 8: Final documentation and cleanup
    ```

  - Each task can later be expanded with sub-bullets describing what it includes.
  - When a task is completed, the agent must change [ ] to [x] and, if needed, add a short note.

---

### Project Structure Rule: Organize Code by Business Domain

To ensure long-term maintainability, clarity, and ease of extension, the entire application must follow a domain-driven folder structure instead of grouping files by technical layer.

This means each “domain” (feature/business unit) contains all of its backend and frontend logic in one place.

---

Requirements

1. Use Business Domains as the Primary Folder Structure

### The project must be split into domains such as
  - Auth
  - Admin
  - Users
  - Settings
  - Teams (future)
  - Billing (future)
  - Notifications (future)
  - Activity (future)
  - Product (project-specific logic)

Each domain represents a feature, not a technical layer.

---

2. Each Domain Must Be Self-Contained

### A domain may contain
  - Backend action logic (controllers, actions, services, etc.)
  - Models/entities related to that domain
  - Policies/permissions
  - Validation rules or Form Requests
  - Routes (either grouped inside the domain or referenced from main routes)
  - Frontend pages (React/Inertia)
  - Frontend components related specifically to that domain
  - Tests (if applicable)
  - Domain-specific config (if needed)

### The guiding rule

Everything related to one feature must live in its corresponding domain folder.

Do not scatter logic across global Laravel folders like Http/Controllers, Http/Requests, Models, or Pages.

---

3. What to Avoid
  - Do not organize controllers in /Http/Controllers by technical grouping.
  - Do not place all models in /Models if they belong to different domains.
  - Do not organize React pages in a flat /Pages folder.
  - Do not mix unrelated domain concerns inside the same directory.

---

4. Expectations for Cursor

### When refactoring or implementing new features
  - Cursor must place all new code inside the correct domain.
  - When touching existing code:
  - Cursor must move files into their appropriate domains.
  - Cursor must update import paths and references accordingly.
  - If a piece of code does not clearly belong to any domain:
  - Cursor must propose the correct domain or ask for clarification.
  - Cursor must avoid introducing new “global” folders unless absolutely necessary.

---

5. Allowed Exceptions

### Certain files are naturally global and should not be moved into domains
  - Laravel’s bootstrapping files (Service Providers, Kernel, Middleware)
  - Exception handling logic
  - Very low-level cross-domain utilities (e.g. helpers, support classes)
  - Shared UI components that are genuinely global (buttons, shells, layout)
  - These belong in a shared Shared/ or Common/ or UI/ folder.

---

Example of Domain-Driven Structure

Below is an example illustrating how the same code should be organized under a domain-driven structure.

❌ WRONG — Technical Layer Structure
```
app/
  Models/
    User.php
    Team.php
  Http/
    Controllers/
      LoginController.php
      RegisterController.php
      UserController.php
      TeamController.php
    Requests/
      UserUpdateRequest.php
  Services/
    AuthService.php
    TeamService.php

resources/js/
  Pages/
    Login.jsx
    Register.jsx
    Users/
      Index.jsx
      Show.jsx
    Teams/
      Index.jsx
```


✅ CORRECT — Domain-Driven Structure
```
app/
  Domain/
    Auth/
      Actions/
      Controllers/
      Services/
      UserFromGoogleResolver.php
      MagicLinkHandler.php

    Users/
      Models/User.php
      Actions/UpdateUserProfile.php
      Controllers/UserAdminController.php

    Admin/
      Controllers/AdminDashboardController.php
      Actions/ToggleAdminStatus.php

    Settings/
      Controllers/SettingsController.php
      Actions/UpdateProfileSettings.php

    Product/
      Controllers/DashboardController.php
      Actions/CustomAppLogic.php

resources/js/
  domains/
    auth/
      pages/LoginPage.tsx
      components/GoogleLoginButton.tsx
      components/MagicLinkForm.tsx

    admin/
      pages/AdminDashboard.tsx
      pages/UsersListPage.tsx
      pages/UserDetailsPage.tsx

    users/
      components/UserCard.tsx

    settings/
      pages/SettingsIndex.tsx
      pages/ProfileSettingsPage.tsx

    product/
      pages/HomeDashboard.tsx
      components/FeatureExample.tsx
```

Notes
  - All Auth-related logic (backend + frontend) lives in the Auth domain.
  - All Admin logic lives in the Admin domain.
  - All Settings logic lives in the Settings domain.
  - All Product-specific logic stays separate under Product.
  - There is no scattering of controllers, pages, or models across unrelated directories.

---

Summary
  - The project must adopt a business-domain–centric architecture.
  - Domains group all related code together (backend + frontend).
  - Cursor must always:
  - Put new code into the correct domain.
  - Refactor existing code into proper domains.
  - Ask questions if the correct domain is unclear.
  - Shared logic belongs in shared folders, not domains.
  - This structure creates a cleaner, more maintainable, scalable codebase.

- ---

### Task Granularity and Autonomy

- The task list in `specs.md` is a **starting point**, not a strict limit.
- The agent is **allowed and encouraged** to:
  - **Create new tasks** when it discovers missing work that is necessary to fulfill the specification (e.g. “Add tests for Docker setup”, “Refactor auth layout to support new login methods”).
  - **Split existing tasks** into smaller subtasks when they are too large or complex (e.g. splitting “Auth & Security” into “Google login”, “Magic Link”, “2FA behavior”).
  - **Expand tasks with clarifying bullet points** to describe their scope more precisely.

- When doing this, the agent must:
  - Keep `specs.md` organized and readable.
  - Preserve the original intent of the roadmap.
  - Clearly mark new or expanded tasks in a way that makes sense in the overall plan.

- The agent should **not hesitate** to adjust the task structure if it makes implementation:
  - Safer,
  - Easier to understand,
  - Or more aligned with best practices.

- Any major restructuring of tasks (merging, renaming, reordering) should be:
  - Done thoughtfully,
  - Explained briefly in `specs.md` or in the conversation,
  - Aligned with the overall goals and general instructions.

- -----

## Task 1: Dockerize the Application (Local Development)

Goal

Provide a simple, robust Docker-based development environment for the Laravel React Starter Kit–based application, so it can be run easily via docker compose with minimal setup.

### The Docker setup must
  - Be as simple as possible, avoiding unnecessary complexity (no Kubernetes, no overly fragmented micro-containers “just because”).
  - Be bullet-proof for local development:
        •	Reliable startup.
        •	Clean shutdown.
        •	Predictable behavior across machines.
  - Use current stable versions of services (PHP, database, etc.) wherever reasonable.
  - Respect and reuse Laravel’s existing best practices, especially:
	    •	Prefer using Laravel (if appropriate) rather than inventing a completely custom Docker stack from scratch, as is the official, well-maintained Docker setup for Laravel.

### Requirements

	1.	Reuse Laravel Ecosystem Where Possible

	2.	Services Required for Local Dev
        At a minimum, the Docker environment must provide:
        •	Application runtime:
            •	PHP (latest stable supported by Laravel).
            •	Node tooling inside the dev environment or a straightforward way to run Vite.
        •	Web server / HTTP access:
            •	A way to access the app at a predictable host/port (e.g. http://localhost:80 or http://localhost:8000).
        •	Database:
            •	A relational database (PostgreSQL), using a recent stable version.
            •	It must integrate with the app via .env configuration.
        •	Optional but recommended:
            •	Redis for queues/cache (if the app uses them now or will later).
            •	These should be part of the default stack if they are common for Laravel + this project.

	3.	Developer Workflow
        The Docker setup must support a smooth local workflow, including:
        •	Starting the project with a single, simple command:
            •	Example: docker compose up.
        •	Stopping containers cleanly.
        •	Running common dev commands easily, such as:
            •	Migrations.
            •	Seeders.
            •	Tests.
            •	Frontend dev server (Vite) for hot reloading.
        The workflow should be documented briefly in the README / specs so a developer knows exactly how to:
  - Start the environment.
  - Access the app in the browser.
  - Run backend and frontend build/dev commands.

	4.	Configuration & Environment Variables
        •	.env (or a specific .env.docker) must be compatible with the Docker configuration:
            •	Correct database host, port, username, password.
            •	Any additional service configuration (Redis, etc.).
        •	The setup must minimize surprises:
            •	No hidden magic; the environment used inside the containers should match what the Laravel app expects.

	5.	Simplicity Over Cleverness
        •	No unnecessary multi-stage over-optimization at the cost of clarity.
        •	No exotic Docker tricks that make onboarding harder.
        •	The focus is on a clear, maintainable, standard Laravel dev environment using Docker.

	6.	Documentation
        •	After Dockerization is complete, there must be a short section (could be in README or specs.md) explaining:
            •	How to start the app using Docker.
            •	How to stop it.
            •	How to run key dev commands (migrations, tests, frontend dev server).
        •	This can be concise but must be accurate and up to date.

Expectations for Cursor on This Task
  - Before implementing, the agent must:
        •	Inspect the current project for:
            •	Existing configuration (if any).
            •	Existing Docker-related files (if any).
        •	Reuse/extend official Laravel setup where possible.
  - After implementing:
        •	Ensure the app can be:
        •	Brought up via Docker.
        •	Accessed in the browser.
        •	Used for both backend and frontend development.
  - specs.md must be updated:
	    •	Mark "Task 1: Dockerize the application for local development" as completed once everything works.
        •	Optionally add a short note listing:
            •	Which services are in the stack.
            •	Which command(s) are used to start the environment.

**Status: Completed**
- Created docker-compose.yml with services: app (PHP 8.4 + Node.js 24), PostgreSQL 18, Redis 8
- Created Dockerfile with PHP 8.4 CLI, required extensions, and Node.js
- Created .dockerignore for optimized builds
- Created .env.example with Docker-compatible defaults (PostgreSQL, Redis)
- Updated README.md with comprehensive Docker development documentation
- Setup uses supervisor to automatically start Laravel server and Vite dev server
- Application accessible at http://localhost:8000, Vite at http://localhost:5173
- Startup command: `docker compose up`

---

## Task 2: Admin Area & User Management

Goal

### Implement the first version of the Admin area of the application, based on the Laravel React Starter Kit, with
  - A dedicated Admin section inside the dashboard.
  - Strict admin-only access (backend + frontend).
  - A User management view (list + detail, with basic actions).
  - A design and UX that feels native to the starter kit (same layout, components, and style).
  - A structure that is easy to extend later (e.g. when Teams, Billing, etc. are added).

This task focuses on users, since the starter kit currently only has the User entity.

---

2.1 Admin Access & Permissions

### Requirements
	1.	Admin flag on User
  - The system must have a way to distinguish admin users from normal users.
  - Use a boolean flag on the user model (e.g. is_admin).
  - There must be a clear, canonical way to check if a user is an admin in code.
	2.	Admin-only routes and backend protection
  - All admin-related routes must:
  - Require authentication.
  - Require that the current user is an admin.
  - It must not be possible to access admin routes by guessing URLs or by enabling UI elements on the client side.
  - Even if a non-admin user tampers with the frontend or makes a direct HTTP call, they must receive a “forbidden” response.
	3.	Admin-only UI
  - The Admin section in the dashboard (sidebar link and any admin-specific navigation) must be:
  - Visible only to admin users.
  - Completely hidden for non-admin users.
  - If a user is not an admin:
  - They must not see the Admin link in the sidebar.
  - They must not be able to access admin pages by URL.
	4.	Admin user bootstrap
  - There must be a documented way (e.g. via seeder or simple instructions) to ensure at least one admin user exists.
  - This can be documented in README / specs:
  - e.g. “Run seeder X” or “Update is_admin manually for your first admin user”.

---

2.2 Admin Section in the Dashboard (UI & Navigation)

### Requirements
	1.	Admin entry point
  - Add an “Admin” section/link in the main dashboard navigation, consistent with the starter kit’s sidebar style.
  - This link should lead to an Admin home/overview page.
	2.	Admin layout
  - The Admin pages must:
  - Use the same main dashboard layout as the rest of the app (sidebar + header).
  - Follow the same typography, spacing, and components (buttons, tables, cards, etc.).
  - Admin pages should feel like a sub-area of the existing dashboard, not a separate application.
	3.	Admin home / overview
  - Provide a simple Admin overview page that can be extended later.
  - At minimum, this page should:
  - Indicate clearly that the user is in the Admin area (title, heading).
  - Show some basic metrics/summary related to users:
  - e.g. total user count, number of admins, latest signups (high-level).

---

2.3 User Management: List & Detail

Since there are no other entities yet, the Admin area must implement User management as its first core feature.

2.3.1 User List (Admin → Users)
### Requirements
  - Provide a Users section under Admin where admins can:
  - See a paginated list of users.
  - See key columns for each user:
  - ID (or some unique identifier).
  - Name.
  - Email.
  - When the account was created.
  - Whether they are an admin.
  - (Optionally) last login / last activity if such data is available or easy to derive.
  - Sort or at least filter/search users by:
  - Name and/or email (basic search is sufficient).
  - The list UI must:
  - Use the existing dashboard styling and components (e.g. table-style layout using shadcn/ui).
  - Show clear states:
  - Loading state.
  - “No users found” state when filters return nothing.

2.3.2 User Detail View
### Requirements
  - Admins must be able to click into a User detail view for each user.
  - The User detail view should present:
  - Basic profile information:
  - Name.
  - Email.
  - Admin status.
  - Created date.
  - Any relevant account/security-related flags that are easy to show now:
  - e.g. whether they have verified their email, whether they have 2FA enabled (if this data is available already).
  - A summary of activity (optional at this stage, or placeholder for later):
  - e.g. last login, last seen, or a placeholder indicating this will be expanded later.
  - The User detail page must:
  - Respect the existing dashboard style.
  - Provide a clear “Back to users list” navigation.

---

2.4 Admin Actions on Users

### The Admin area must provide sensible, safe actions on users. These actions must be
  - Accessible only to admins.
  - Integrated with backend access control (not just visible/invisible UI).

### At a minimum, the following should be supported
	1.	Toggle admin status
  - Admins must be able to promote a user to admin, or demote an admin back to a regular user.
  - The system must prevent obviously unsafe operations:
  - For example, it should be impossible to demote the last remaining admin, to avoid locking everyone out.
  - UI should:
  - Clearly indicate whether a user is an admin.
  - Provide obvious controls to change that status (button, switch, etc.).
  - Show confirmation or feedback (success/error messages).
	2.	Edit basic user information (optional but desirable)
  - Consider allowing admins to edit:
  - Name.
  - Email.
  - If implemented, ensure:
  - Changes obey validation rules.
  - If there is any conflict with future "self-service" settings or email verification flows, the system should:
  - Prefer consistency and safety.
	3.	User status / deactivation (optional)
  - If the project decides to support a “deactivated” or “banned” state (e.g. is_active flag), the Admin area should:
  - Allow toggling that status.
  - Ensure deactivated users cannot log in.
  - This is optional for this first pass. If not implemented now, the Admin UI should be designed so it can be easily extended later with such a flag.

Note: If any of these actions (like deactivation) are not implemented now, they should not be surfaced as clickable controls. Do not show UI that does nothing.

---

2.5 Security & Robustness

### Requirements
  - All admin operations must be protected:
  - Authenticated.
  - Admin-authorized (checking the admin flag or equivalent).
  - There must be no way for a non-admin user to:
  - Access admin lists or detail views.
  - Modify users via admin endpoints.
  - Input validation must be in place for:
  - Search parameters.
  - Edit operations (e.g. name/email formats).
  - Admin status changes.

### Any errors (e.g. invalid input, permission violation, unexpected issues) must
  - Return appropriate HTTP status codes.
  - Show clear, user-friendly messages in the UI (not raw stack traces).

---

2.6 Integration With the Rest of the System

Even though only User exists for now, this Admin area is the foundation for future admin features (Teams, Billing, Activity, etc.).

### Requirements
  - The Admin area must be designed to be extendable:
  - Admin navigation should be structured so that new sections (e.g. Teams, Billing) can be added later without redesigning everything.
  - The Admin home/overview page can be extended later with more metrics/cards.
  - Any changes to shared layout/nav must:
  - Not break existing user-facing pages.
  - Maintain a coherent user experience between normal dashboard pages and the Admin area.

---

2.7 Specs & Task List Updates

### Once the Admin area and user management are implemented
  - The specs.md roadmap must be updated:
  - Mark the "Admin basics & User management" task as completed ([x]).
  - Optionally list a short summary, such as:
  - "Admin area added with user list, user detail, admin-only access, and admin toggling."
  - If, during implementation, it becomes clear that additional tasks are needed (e.g. "Track last login" or "Implement user deactivation system"), the agent may:
  - Add new tasks/subtasks to specs.md.
  - Mark them as TODO or In Progress.
  - Implement them if they are necessary to make the Admin feature coherent and robust.

**Status: Completed**
- Admin area implemented with strict access control (middleware + UI hiding)
- Admin overview page with user metrics and "Manage Users" button
- User list page with pagination, search, and suspended status indicators
- User detail page with profile information, edit capabilities, and account actions
- Admin status toggle with last admin protection
- User suspension system: suspend/unsuspend functionality with authentication prevention
- All admin operations protected with backend validation and authorization
- AdminUserSeeder created for easy admin user creation
- Documentation updated in README.md

---

## Task 3: Auth & Security (Google OAuth + Magic Link Only)

This task supersedes previous Auth/2FA specs.
For now, the app must not use email+password login or 2FA.
Only Google OAuth and Magic Link are used for authentication.

---

3.1 Overall Auth Behavior

### The authentication system must
  - Use Laravel Fortify as the core auth backend (as provided by the Laravel React Starter Kit), but:
  - Email + password login and registration must be disabled from a user perspective.
  - Support exactly two login methods:
	1.	Login with Google (OAuth).
	2.	Login with Magic Link (passwordless email link).

### General rules
  - All login methods must log into the same User model.
  - If a user already exists with a given email:
  - Logging in through Google or Magic Link with that email must log into the existing account, not create a duplicate.
  - There must be no visible or usable password-based login or registration in the UI.
  - Two-Factor Authentication (2FA) must be completely disabled (no UI, no prompts, no requirement).

---

3.2 Login Screen UX

### The login screen (from the Starter Kit) must be adapted so that
  - It shows only:
  - A “Continue with Google” (or similar) button.
  - A Magic Link login form (email input + “Send login link” button).
  - It must not show:
  - Email/password fields.
  - Any references to “password”, “forgot password”, or “register with email”.

### Design constraints
  - Use the existing auth layout and design of the starter kit.
  - Reuse existing form and button styles (shadcn/ui + Tailwind) so it visually fits perfectly.
  - Error messages (e.g., “Invalid magic link”, “Login failed”) must:
  - Display in the same style as existing validation errors.
  - Be clear and user-friendly.

### Behavior
  - After a successful login (via Google or Magic Link), the user is redirected to the main dashboard.
  - If login fails (invalid token, OAuth error, etc.), the user stays on the login screen with a helpful error.

---

3.3 Registration Behavior (No Email+Password Sign-Up)

For now, there should be no explicit email+password registration flow.

### Rules
  - No registration form for email/password:
  - Hide or remove any “Sign up” / “Register” UI that assumes email+password.
  - User creation happens implicitly via:
  - First successful Google login.
  - First successful Magic Link login (if you choose to auto-create users for unknown emails).

### Behavior for new emails
  - For Google:
  - If a Google login succeeds and no user exists for that email:
  - A new user account is created automatically.
  - For Magic Link:
  - You must choose and implement one of the two behaviors (and be consistent):
  - Option A (auto-create):
  - If a magic link is requested for an unknown email:
  - Create a new user.
  - Send the link.
  - Option B (no auto-create):
  - If a magic link is requested for an unknown email:
  - Show an error (“No account found for this email”).
  - Whichever option is used must be clearly documented in the code comments or README.

All visible “Register” links and forms that imply email + password must be hidden/removed.

---

3.4 Login With Google (OAuth)

Add Login with Google as a primary authentication method.

### Requirements
  - UX:
  - The login page must include a “Continue with Google” button.
  - Clicking it should start the OAuth flow.
  - Behavior:
  - User is redirected to Google’s OAuth consent.
  - On callback:
  - Extract the email address (must be trusted/verified as per Google’s data).
  - If a user with that email exists:
  - Log them in.
  - If not:
  - Create a new user with that email (and name if available).
  - After completion, redirect user to the dashboard.
  - Edge cases:
  - If Google doesn’t provide an email or returns an error:
  - Do not create an unusable account.
  - Show a friendly error and suggest trying again or contacting support.

### Interaction with other auth methods
  - If a user previously logged in with Magic Link for foo@example.com, and then logs in with Google for foo@example.com:
  - They must be treated as the same account.

---

3.5 Magic Link Login (Passwordless)

Implement Magic Link login.

### Requirements
  - UX on login page:
  - A simple form:
  - Input: email address.
  - Button: “Send login link” / similar.
  - When submitted:
  - Show a success state (“If an account exists, we sent a login link”).
  - Behavior:
  - When a magic link is requested:
  - Depending on the chosen strategy:
  - Auto-create strategy:
  - If user exists:
  - Generate a single-use, time-limited token.
  - Send a login email with a link.
  - If user does not exist:
  - Create a new user record.
  - Generate token and send login email.
  - No auto-create strategy:
  - If user exists:
  - Generate token and send login email.
  - If user does not exist:
  - Show an error or a neutral success message but do not create a user.
  - When user clicks the magic link:
  - Validate token (correct, not expired, not already used).
  - If valid:
  - Log the user in.
  - Mark the token as used.
  - Redirect to dashboard.
  - If invalid or expired:
  - Show a friendly error and link back to login to request a new link.
  - Security:
  - Tokens must be random, unguessable, single-use, and time-limited.
  - Failed or expired token use must not leak which emails are registered.

### Interaction with other auth methods
  - Magic link login must respect the “one email = one user” rule.
  - It must co-exist cleanly with Google login.

---

3.6 2FA and Password Features: Disabled

For this stage, 2FA and password-based features must be fully disabled.

### Requirements
  - No 2FA:
  - Do not prompt for 2FA after login.
  - Do not show 2FA settings pages.
  - Do not expose QR codes, recovery codes, or any 2FA flows.
  - No password-based login or password settings:
  - Do not show “Change Password”, “Set Password”, or “Forgot Password” UI.
  - Do not show a “Password” tab in Settings.
  - Fortify:
  - Under the hood, Fortify’s password and 2FA features can remain installed, but:
  - They must be effectively “turned off” for users:
  - No visible routes/UI for these behaviors.
  - No flows that rely on “current password”.

If any underlying routes remain (for technical reasons), the UI must not expose them, and they must not be reachable in normal user flows.

---

3.7 Settings Area Adjustments (Security Pages)

### The Settings area must be updated to reflect the new auth model
  - Remove or hide:
  - Password settings page.
  - Two-Factor Authentication (2FA) settings page.
  - Ensure there are no dead links:
  - The Settings navigation must not contain entries pointing to disabled pages.
  - If there is a “Security” section:
  - It can either be temporarily hidden entirely.
  - Or kept as a minimal “info only” section explaining that:
  - Login is via Google or Magic Link.
  - Additional security options may be added later.
  - But it must not show non-functional or broken widgets.

Goal: the Settings UI should feel coherent and not suggest features that do not exist.

---

3.8 Integration With Admin Area

The Admin section (already implemented) must remain consistent with the new auth behavior.

### Requirements
  - Admin user list / detail views:
    - Must not assume users have passwords.
    - Must not display broken controls related to password or 2FA management.
  - It is acceptable for Admin to see:
    - Basic auth-related info (e.g., user’s email, when they signed up, maybe whether they’ve ever logged in).
  - Admin must not:
    - Be given 2FA controls (since 2FA is disabled).
    - Use password-reset or password-set flows that no longer exist in the UI.

### If any admin feature related to passwords or 2FA was previously planned or stubbed, it must be either
  - Removed for now, or
  - Clearly marked as not implemented and not surface in the UI.

---

3.9 Security & Edge Cases

Even without passwords and 2FA, auth must be secure and robust.

### The system must correctly handle
  - Invalid Google OAuth callbacks:
    - Show a clean error, no partial/broken accounts.
  - Magic Link abuse attempts:
    - Rate limiting (Laravel’s built-in throttling can be used where applicable).
    - Expired or already-used tokens.
  - Email enumeration:
    - Magic link flows must be careful not to reveal if an email is registered (depending on the chosen UX).
  - Session handling:
    - Ensure users are properly logged out when requested (Logout behavior stays standard).

All error conditions must be handled gracefully with user-friendly messages.

---

3.10 Specs & Task List Updates

### After implementing this Auth behavior
  - Update specs.md:
  - Mark “Task 3: Auth & Security (Google OAuth + Magic Link Only)” as completed ([x]) or update its status to “Done/In Review”.
  - Optionally add a brief note, such as:
  - “Auth now uses only Google OAuth and Magic Link. Email+password and 2FA are disabled. Login and Settings reflect this.”
  - If, during implementation, you discover necessary follow-up tasks (e.g. “track last login timestamp”, “add login history for admin”), you may:
  - Add them as new tasks or subtasks in specs.md.
  - Leave them as TODO unless required to make the current auth flow coherent.

---

## Task 4: Billing, Subscriptions & Credits (Hybrid Model, User-Based)

### Goal

Implement a hybrid monetization system where:

- Each **User is the billing entity** (no Teams).
- The app supports **subscription plans** via Stripe (Laravel Cashier recommended).
- The app supports a **credit system** for metered/AI-heavy usage.
- Subscription plans:
  - Grant premium features.
  - Include monthly credits.
  - Optionally give discounts or bonuses.
- Users can **buy additional credits** as one-off purchases (credit packs).
- Feature access is based on:
  - The user’s **plan** (free vs paid)
  - The user’s **credit balance**

This system must be clean, maintainable, and aligned with standard SaaS practices.

---

## 4.1 Core Model & Assumptions

- Authentication uses Google OAuth + Magic Link.
- User emails are **immutable** (cannot be changed).
- Billing is **per user**.
- No Teams/Workspaces.
- Each user has:
  - A `plan` (derived from subscription or default free)
  - A `subscription_status`
  - A `credits_balance` integer

Credits come from:
- Monthly plan allocations
- One-off credit pack purchases
- Optional admin adjustments

---

## 4.2 Plans & Configuration

All plan definitions must exist in a single config file (example: `config/plans.php`).

Each plan must define:

- `key` (identifier, e.g. `free`, `pro`)
- `name`
- `type`: `free` or `paid`
- `stripe_price_id` (nullable for free plans)
- `monthly_credits`
- `features`: array of enabled feature keys

Example structure:

```
'plans' => [
  'free' => [
    'key' => 'free',
    'name' => 'Free',
    'type' => 'free',
    'monthly_credits' => 10,
    'features' => ['basic_usage'],
  ],
  'pro' => [
    'key' => 'pro',
    'name' => 'Pro',
    'type' => 'paid',
    'interval' => 'monthly',
    'stripe_price_id' => env('STRIPE_PRICE_PRO_MONTHLY'),
    'monthly_credits' => 200,
    'features' => ['basic_usage', 'advanced_usage', 'priority'],
  ],
]
```

This config must be used by the Pricing page, Billing page, and feature gating logic.

---

## 4.3 Subscription System (Stripe)

Subscriptions operate on a per-user basis.

Rules:

- A user can have **zero or one** active subscription.
- If no subscription exists, the user is automatically on the `free` plan.
- A Stripe subscription determines the user’s paid plan.

Required flows:

### Start subscription
- User selects a plan.
- System creates or reuses a Stripe customer.
- User is redirected to Stripe Checkout.
- On success, subscription + plan is stored internally.

### Change plan
- Backend updates Stripe subscription accordingly.
- Internal plan mapping updates.

### Cancel subscription
- User cancels their plan.
- Access continues until period end.
- After expiration → return to free plan.

### Stripe Billing Portal
- Provide a direct “Manage billing” link to customer portal.

### Stripe Webhooks
Must update:
- Subscription created
- Subscription updated
- Subscription canceled
- Payment failures

Webhook results update internal subscription fields.

---

## 4.4 Credits System

Credits are for AI-heavy or metered operations.

User model must store:
- `credits_balance`

A `credit_transactions` table is recommended to track:
- Additions (subscription monthly credits, credit packs)
- Subtractions (usage)
- Context (feature name / action)

### Credits Sources

1. Monthly plan allocation
2. One-off credit packs via Stripe Checkout
3. Admin adjustments (future)

### Credits Usage

Before performing a credit-costing action:
- Check user has sufficient credits
- If not enough:
  - Trigger credits paywall
- If enough:
  - Deduct credits
  - Record transaction (optional)

Credits must never go negative.

---

## 4.5 Feature Gating (Plans + Credits)

Every feature must define:
- Required plan(s)
- Required credit cost

Examples:

- Basic feature  
  - plans: free, pro  
  - credits: 0

- Light AI  
  - plans: free, pro  
  - credits: 1

- Heavy AI  
  - plans: pro  
  - credits: 5

### Required Helper Logic

Implement helper methods:

```
$user->onPlan('pro')
$user->canUseFeature('advanced_ai')
$user->hasCredits($amount)
$user->chargeCredits($amount, $context)
```

### Paywalls

Two reusable paywall UI components:

1. **Plan Paywall**  
   - “This feature requires a paid plan.”  
   - Button to upgrade  

2. **Credits Paywall**  
   - “You don’t have enough credits.”  
   - Button to buy credits  

Cursor must reuse these consistently.

---

## 4.6 Pricing Page

Pass for now.

---

## 5.7 Billing Page (User Settings → Billing)

The Billing page must show:

- Current plan (free or paid)
- Subscription status
- Credits balance
- Monthly credits allocation
- Available actions:
  - Upgrade
  - Change plan
  - Cancel plan
  - Open Stripe Billing Portal
  - Buy Credits

UI must follow the dashboard style.

---

## 4.8 Buy Credits Flow

Flow requirements:

1. User clicks “Buy Credits”
2. Chooses pack (e.g. 50, 100, 500)
3. Stripe Checkout session is created
4. On payment success:
   - Webhook or return route increments credits_balance
5. User sees updated credits

Credit packs must be separate Stripe products.

---

## 4.9 Paywall Logic & UX

When a user cannot use a feature:

### If plan is insufficient:
- Show Plan Paywall component
- Suggest upgrade

### If credits insufficient:
- Show Credits Paywall component
- Suggest buying a pack

These components must be:
- Reusable
- Consistent
- Positioned similarly across all features

---

## 4.10 Admin Panel – Billing Overview

Admin panel must show details for the user in the user info

- Current plan
- Subscription status
- Next billing date
- Credits balance
- Stripe customer ID

Admin actions:
- v1: **view-only**
- Future:
  - Manual credit adjustments

---

## 4.11 Invariants & Edge Cases

1. Every user always has a plan  
   No subscription → plan = free.

2. Credits cannot go negative  
   Must check before deducting.

3. Email addresses are immutable  
   Stable Stripe customer identity.

4. Subscription data must always sync with Stripe  
   Use webhooks for updates.

5. No deletion of user data on downgrade  
   Downgrade only restricts access, never deletes content.

---

## 4.12 UI & Code Structure Expectations (for Cursor)

Cursor must:

- Organize all billing, subscription, and credit logic in a **Billing/Payments domain**.
- Follow dashboard UI guidelines:
  - shadcn UI components
  - Tailwind spacing
  - lucide icons
- Use a single unified gating mechanism:
  - Check plan
  - Check credits
  - Trigger appropriate paywall

Avoid duplicating logic or creating ad-hoc billing code.

---

## 4.13 Specs & Task List Updates

After implementation:

- Mark Task 4 as completed.
- Add follow-up tasks if discovered, such as:
  - Credit transaction history
  - Trial logic
  - Coupons/promotions
  - Monthly credit reset scheduler
  - Additional plans
- Ensure all UI and backend code follows the design and architectural guidelines above.

---


# Project Tasks / Roadmap

- [x] Task 0: Set up general instructions and guidelines
- [x] Task 1: Dockerize the application for local development
  - Services included: PHP 8.4, PostgreSQL 18, Redis 8, Node.js 24
  - Startup command: `docker compose up`
  - Laravel server and Vite dev server start automatically
- [x] Task 2: Admin Area & User Management
  - Admin access & permissions: Added `is_admin` flag to users, `EnsureUserIsAdmin` middleware, admin-only routes and UI
  - Admin section in dashboard: Admin overview page with metrics (total users, admin count, recent signups), "Manage Users" button linking to user list
  - User management: Paginated user list with search/filter, user detail view with profile information and security flags
  - Admin actions: Toggle admin status (with last admin protection), edit user name/email, suspend/unsuspend users
  - User suspension: Added `suspended_at` column, suspend/unsuspend functionality, prevented suspended users from logging in
  - Security: All admin operations protected with middleware, input validation, error handling
  - Admin user bootstrap: Created `AdminUserSeeder` with documentation in README
- [x] Task 3: Auth & Security (Google OAuth + Magic Link Only)
  - Disabled email+password login and 2FA: Removed registration, password reset, and 2FA features from Fortify config
  - Google OAuth: Implemented Google OAuth login with Laravel Socialite, auto-create users, suspended user prevention
  - Magic Link: Implemented passwordless magic link authentication with auto-create strategy, 15-minute token expiration, rate limiting
  - Login UI: Completely redesigned login page with Google OAuth button and Magic Link form, removed password fields and registration links
  - Settings: Removed Password and Two-Factor Auth from Settings navigation and routes
  - Security: Suspended users blocked from both auth methods, rate limiting for magic link requests, proper error handling
  - Documentation: Updated README with Google OAuth setup instructions and authentication overview
- [x] Task 4: Billing, Subscriptions & Credits (Hybrid Model, User-Based) - COMPLETE
  - Laravel Cashier installed and configured
  - Database migrations: billing fields on users, credit_transactions table
  - Config-driven plans (config/plans.php) with free/pro/business tiers
  - Credit system: HasCredits trait, CreditTransaction model
  - Subscription system: HasSubscription trait, Billable integration
  - Services: PlanService, CreditService, FeatureGateService
  - Controllers: BillingController, CheckoutController, StripeWebhookController
  - Routes: billing routes, webhook endpoint
  - Frontend components: PlanPaywall, CreditsPaywall, FeatureGate, CreditBalance
  - Billing page UI with plan management and credit purchases
  - TypeScript types for billing entities
  - Admin panel integration: billing info displayed on user details
  - Billing section added to Settings dialog
  - Billing link added to main navigation sidebar
  - **Note:** Monthly credit reset command and Stripe product configuration still needed for production

---