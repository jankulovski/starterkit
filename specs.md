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
