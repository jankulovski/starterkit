General Instructions for the Agent

You are working on a Laravel + React + Inertia project based on the official Laravel React Starter Kit.
Your role is to behave as a senior / expert full-stack developer who:
	•	Respects the existing framework and starter kit
	•	Reuses as much as possible
	•	Implements features end-to-end (backend + frontend + admin + UX)
	•	Keeps the codebase coherent and maintainable

⸻

1. Respect the Framework and Starter Kit
	•	Always stick to Laravel and the official Laravel React Starter Kit patterns.
	•	Reuse existing:
	•	Laravel features (Fortify, Eloquent, validation, policies, middleware, queues, notifications, etc.)
	•	React + Inertia patterns already used in the starter kit
	•	Tailwind/shadcn/ui components and design tokens
	•	Prefer first-party Laravel packages (Fortify, Socialite, Cashier, etc.) and well-adopted libraries.
	•	Do not reinvent things the framework already solves (auth flows, validation, forms, settings patterns, etc.).

If you believe a different approach is materially better, you must:
	1.	Explain why it is better.
	2.	Propose it clearly.
	3.	Ask for confirmation before applying large or breaking changes.

⸻

2. Follow the Existing Design & UX
	•	The UI must match the design of the Laravel React Starter Kit:
	•	Same layout structure (sidebar, header, content)
	•	Same typography, spacing, and shadcn/ui styles
	•	Same design patterns for forms, tables, alerts, buttons
	•	Do not introduce a different design language or new component systems.
	•	When adding new screens, layouts, or components:
	•	They must look like they belong to the starter kit
	•	Use existing layout wrappers, components, and utility patterns

⸻

3. Work With the Whole System in Mind

Whenever implementing or modifying a feature:
	•	Think holistically across the entire system:
	•	How does this affect authentication?
	•	How does this affect settings?
	•	How does this affect teams?
	•	How does this affect billing?
	•	How does this appear in the dashboard UI?
	•	Does the admin need visibility?
	•	The feature must be implemented end-to-end:
	•	Backend logic
	•	Frontend UI
	•	Dashboard integration
	•	Admin visibility (if needed)
	•	Settings + Security implications

Never leave “half-implemented” functionality.

If a feature impacts global application flows (auth, team selection, subscription checks, navigation), update all related areas to keep the system coherent and predictable.

⸻

4. Always Refer Back to the Specification
	•	Treat the specification as the source of truth.
	•	Before coding anything:
	•	Re-read the relevant section of the spec
	•	Check whether the change connects to or conflicts with another domain
	•	Ensure consistency with previously implemented areas
	•	Maintain an internal model of:
	•	What is already implemented
	•	What remains
	•	How all parts fit together

If you detect conflicts between:
	•	The spec and the codebase, or
	•	Different parts of the spec

Then you must:
	1.	Call out the conflict
	2.	Propose solutions
	3.	Ask for clarification before proceeding

⸻

5. Ask Questions When Requirements Are Unclear

If any requirement is:
	•	Ambiguous
	•	Incomplete
	•	Conflicting
	•	Unusual
	•	Risky or inconsistent with best practices

You must not guess.
You should:
	1.	Describe the ambiguity
	2.	Present reasonable options
	3.	Ask which one to implement

If immediate clarification is not possible, choose the least surprising and most common solution and clearly document the assumption.

⸻

6. Follow Common Patterns and Best Practices

Your implementations must:
	•	Use idiomatic Laravel:
	•	Eloquent models + relationships
	•	Form requests
	•	Policies / gates
	•	Middleware for access control
	•	Events + listeners where appropriate
	•	Config-driven behavior
	•	Use idiomatic React + Inertia + TypeScript:
	•	Functional components
	•	Hooks for state and data
	•	Coherent directory structure
	•	Reuse existing components and patterns
	•	Follow best practices for:
	•	Application security
	•	Validation and error handling
	•	Authorization (never rely only on the frontend)
	•	Performance (avoid N+1 queries, unnecessary renders)
	•	Maintainability (modular code, no “god files”)

If the specification leads toward an anti-pattern, you must:
	1.	Identify the issue
	2.	Propose a better alternative
	3.	Ask whether to adopt the improved approach

⸻

7. Keep Changes Clean, Safe, and Well-Integrated

When implementing new features:
	•	Avoid unnecessary breaking changes
	•	Keep global layouts predictable
	•	Make sure new behavior is guarded by backend access control
	•	Do not rely on visibility alone—use middleware/policies
	•	Ensure the dashboard navigation, URLs, and access checks remain consistent

All logic must be protected properly:
	•	Admin routes must require admin permission
	•	Team-scoped routes must check the current team
	•	Subscription-protected features must check the subscription

⸻

8. Error Handling, Edge Cases, and “Bullet-Proof” Behavior

All features must gracefully handle:
	•	Validation errors
	•	Permission failures
	•	External service failures (Stripe, Google OAuth)
	•	Missing or incomplete user data
	•	Account states like:
	•	Users without a password
	•	Users without 2FA
	•	Teams without subscriptions
	•	Billing trials ending
	•	Team roles changing mid-session

Provide clear user-facing messages, not framework errors.

The goal is to make the system robust, predictable, and safe in all reasonable edge cases.

⸻

9. Documentation & Developer Experience
	•	Update or maintain helpful documentation when major behaviors are introduced or changed.
	•	Document:
	•	Required environment variables
	•	How to configure Stripe and Google OAuth
	•	How to enable/disable features (teams, billing, admin)
	•	How to create an admin user
	•	How new domains are structured

Make the template easy for a developer to extend and understand.

⸻

10. Summary of Expectations

You must:
	•	Reuse the framework and starter kit as much as possible
	•	Match the starter kit’s UI and UX
	•	Implement features fully, not partially
	•	Think holistically about interactions across domains
	•	Respect the spec, and revisit it often
	•	Ask questions when unclear
	•	Behave as an expert developer, not just a code generator


⸻

# Project Tasks / Roadmap

- [x] Task 0: Set up general instructions and guidelines
- [x] Task 1: Dockerize the application for local development
  - Services included: PHP 8.4, PostgreSQL 18, Redis 8, Node.js 24
  - Startup command: `docker compose up`
  - Laravel server and Vite dev server start automatically

⸻

Task Management and specs.md Usage

To coordinate work and keep an overview of what is done and what remains, the project must use a single specification file (e.g. specs.md) as a living TODO / roadmap.

Expectations for specs.md
	•	specs.md must:
	•	Contain a high-level list of tasks (features / domains / improvements) in TODO form.
	•	Indicate the status of each task (e.g. Not started / In progress / Done).
	•	Be updated as work progresses, so it always reflects the current state of the project.
	•	The agent (Cursor) must:
	•	Read specs.md before starting any new task to understand:
	•	What has already been done.
	•	What is in progress.
	•	What is planned next.
	•	Update specs.md after completing a task:
	•	Mark the task as completed (e.g. check the checkbox, update status).
	•	Optionally add a short note about what was actually implemented if helpful.
	•	Not delete history:
	•	Completed tasks should remain in the file (marked as done), not removed.
	•	This keeps a clear record of progress.

Structure Guidelines for specs.md
	•	Use a clear, human-readable structure, for example:
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

	•	Each task can later be expanded with sub-bullets describing what it includes.
	•	When a task is completed, the agent must change [ ] to [x] and, if needed, add a short note.

⸻

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

------

Task 1: Dockerize the Application (Local Development)

Goal

Provide a simple, robust Docker-based development environment for the Laravel React Starter Kit–based application, so it can be run easily via docker compose with minimal setup.

The Docker setup must:
	•	Be as simple as possible, avoiding unnecessary complexity (no Kubernetes, no overly fragmented micro-containers “just because”).
	•	Be bullet-proof for local development:
        •	Reliable startup.
        •	Clean shutdown.
        •	Predictable behavior across machines.
	•	Use current stable versions of services (PHP, database, etc.) wherever reasonable.
	•	Respect and reuse Laravel’s existing best practices, especially:
	    •	Prefer using Laravel (if appropriate) rather than inventing a completely custom Docker stack from scratch, as is the official, well-maintained Docker setup for Laravel.

Requirements:

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
	•	Start the environment.
	•	Access the app in the browser.
	•	Run backend and frontend build/dev commands.

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
	•	Before implementing, the agent must:
        •	Inspect the current project for:
            •	Existing configuration (if any).
            •	Existing Docker-related files (if any).
        •	Reuse/extend official Laravel setup where possible.
	•	After implementing:
        •	Ensure the app can be:
        •	Brought up via Docker.
        •	Accessed in the browser.
        •	Used for both backend and frontend development.
	•	specs.md must be updated:
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

⸻

Task 2: Admin Area & User Management

Goal

Implement the first version of the Admin area of the application, based on the Laravel React Starter Kit, with:
	•	A dedicated Admin section inside the dashboard.
	•	Strict admin-only access (backend + frontend).
	•	A User management view (list + detail, with basic actions).
	•	A design and UX that feels native to the starter kit (same layout, components, and style).
	•	A structure that is easy to extend later (e.g. when Teams, Billing, etc. are added).

This task focuses on users, since the starter kit currently only has the User entity.

⸻

2.1 Admin Access & Permissions

Requirements:
	1.	Admin flag on User
	•	The system must have a way to distinguish admin users from normal users.
	•	Use a boolean flag on the user model (e.g. is_admin).
	•	There must be a clear, canonical way to check if a user is an admin in code.
	2.	Admin-only routes and backend protection
	•	All admin-related routes must:
	•	Require authentication.
	•	Require that the current user is an admin.
	•	It must not be possible to access admin routes by guessing URLs or by enabling UI elements on the client side.
	•	Even if a non-admin user tampers with the frontend or makes a direct HTTP call, they must receive a “forbidden” response.
	3.	Admin-only UI
	•	The Admin section in the dashboard (sidebar link and any admin-specific navigation) must be:
	•	Visible only to admin users.
	•	Completely hidden for non-admin users.
	•	If a user is not an admin:
	•	They must not see the Admin link in the sidebar.
	•	They must not be able to access admin pages by URL.
	4.	Admin user bootstrap
	•	There must be a documented way (e.g. via seeder or simple instructions) to ensure at least one admin user exists.
	•	This can be documented in README / specs:
	•	e.g. “Run seeder X” or “Update is_admin manually for your first admin user”.

⸻

2.2 Admin Section in the Dashboard (UI & Navigation)

Requirements:
	1.	Admin entry point
	•	Add an “Admin” section/link in the main dashboard navigation, consistent with the starter kit’s sidebar style.
	•	This link should lead to an Admin home/overview page.
	2.	Admin layout
	•	The Admin pages must:
	•	Use the same main dashboard layout as the rest of the app (sidebar + header).
	•	Follow the same typography, spacing, and components (buttons, tables, cards, etc.).
	•	Admin pages should feel like a sub-area of the existing dashboard, not a separate application.
	3.	Admin home / overview
	•	Provide a simple Admin overview page that can be extended later.
	•	At minimum, this page should:
	•	Indicate clearly that the user is in the Admin area (title, heading).
	•	Show some basic metrics/summary related to users:
	•	e.g. total user count, number of admins, latest signups (high-level).

⸻

2.3 User Management: List & Detail

Since there are no other entities yet, the Admin area must implement User management as its first core feature.

2.3.1 User List (Admin → Users)
Requirements:
	•	Provide a Users section under Admin where admins can:
	•	See a paginated list of users.
	•	See key columns for each user:
	•	ID (or some unique identifier).
	•	Name.
	•	Email.
	•	When the account was created.
	•	Whether they are an admin.
	•	(Optionally) last login / last activity if such data is available or easy to derive.
	•	Sort or at least filter/search users by:
	•	Name and/or email (basic search is sufficient).
	•	The list UI must:
	•	Use the existing dashboard styling and components (e.g. table-style layout using shadcn/ui).
	•	Show clear states:
	•	Loading state.
	•	“No users found” state when filters return nothing.

2.3.2 User Detail View
Requirements:
	•	Admins must be able to click into a User detail view for each user.
	•	The User detail view should present:
	•	Basic profile information:
	•	Name.
	•	Email.
	•	Admin status.
	•	Created date.
	•	Any relevant account/security-related flags that are easy to show now:
	•	e.g. whether they have verified their email, whether they have 2FA enabled (if this data is available already).
	•	A summary of activity (optional at this stage, or placeholder for later):
	•	e.g. last login, last seen, or a placeholder indicating this will be expanded later.
	•	The User detail page must:
	•	Respect the existing dashboard style.
	•	Provide a clear “Back to users list” navigation.

⸻

2.4 Admin Actions on Users

The Admin area must provide sensible, safe actions on users. These actions must be:
	•	Accessible only to admins.
	•	Integrated with backend access control (not just visible/invisible UI).

At a minimum, the following should be supported:
	1.	Toggle admin status
	•	Admins must be able to promote a user to admin, or demote an admin back to a regular user.
	•	The system must prevent obviously unsafe operations:
	•	For example, it should be impossible to demote the last remaining admin, to avoid locking everyone out.
	•	UI should:
	•	Clearly indicate whether a user is an admin.
	•	Provide obvious controls to change that status (button, switch, etc.).
	•	Show confirmation or feedback (success/error messages).
	2.	Edit basic user information (optional but desirable)
	•	Consider allowing admins to edit:
	•	Name.
	•	Email.
	•	If implemented, ensure:
	•	Changes obey validation rules.
	•	Email changes respect verification rules (if needed).
	•	If there is any conflict with future “self-service” settings or email verification flows, the system should:
	•	Prefer consistency and safety.
	•	Potentially restrict email changes to user-side only and keep admin editing to name and flags.
	3.	User status / deactivation (optional)
	•	If the project decides to support a “deactivated” or “banned” state (e.g. is_active flag), the Admin area should:
	•	Allow toggling that status.
	•	Ensure deactivated users cannot log in.
	•	This is optional for this first pass. If not implemented now, the Admin UI should be designed so it can be easily extended later with such a flag.

Note: If any of these actions (like deactivation) are not implemented now, they should not be surfaced as clickable controls. Do not show UI that does nothing.

⸻

2.5 Security & Robustness

Requirements:
	•	All admin operations must be protected:
	•	Authenticated.
	•	Admin-authorized (checking the admin flag or equivalent).
	•	There must be no way for a non-admin user to:
	•	Access admin lists or detail views.
	•	Modify users via admin endpoints.
	•	Input validation must be in place for:
	•	Search parameters.
	•	Edit operations (e.g. name/email formats).
	•	Admin status changes.

Any errors (e.g. invalid input, permission violation, unexpected issues) must:
	•	Return appropriate HTTP status codes.
	•	Show clear, user-friendly messages in the UI (not raw stack traces).

⸻

2.6 Integration With the Rest of the System

Even though only User exists for now, this Admin area is the foundation for future admin features (Teams, Billing, Activity, etc.).

Requirements:
	•	The Admin area must be designed to be extendable:
	•	Admin navigation should be structured so that new sections (e.g. Teams, Billing) can be added later without redesigning everything.
	•	The Admin home/overview page can be extended later with more metrics/cards.
	•	Any changes to shared layout/nav must:
	•	Not break existing user-facing pages.
	•	Maintain a coherent user experience between normal dashboard pages and the Admin area.

⸻

2.7 Specs & Task List Updates

Once the Admin area and user management are implemented:
	•	The specs.md roadmap must be updated:
	•	Mark the “Admin basics & User management” task as completed ([x]).
	•	Optionally list a short summary, such as:
	•	“Admin area added with user list, user detail, admin-only access, and admin toggling.”
	•	If, during implementation, it becomes clear that additional tasks are needed (e.g. “Track last login” or “Implement user deactivation system”), the agent may:
	•	Add new tasks/subtasks to specs.md.
	•	Mark them as TODO or In Progress.
	•	Implement them if they are necessary to make the Admin feature coherent and robust.

⸻
