## UI Guidelines (Inspired by OpenAI Platform Dashboard)

**Overall Goal**

- The UI should feel similar in spirit to the **OpenAI Platform Dashboard**, using:
  - Clean, minimal layout
  - Clear hierarchy
  - Comfortable whitespace
  - Subtle borders and dividers
- The design system is **shadcn/ui + Tailwind CSS**. Do not introduce other design systems.

---

### 1. Layout & Structure

- Use an **app shell layout**:
  - **Left sidebar**: navigation with icons and labels.
  - **Top header**: page title, breadcrumbs, optional actions (primary button, filters).
  - **Main content**: card-based sections, tables, forms, charts.

- The app should be **full height**:
  - `min-h-screen` on root layout.
  - Sidebar fixed or sticky on the left.
  - Content area scrolls independently.

- Spacing:
  - Use a consistent scale (e.g. `gap-4`, `gap-6`, `p-4`, `p-6`).
  - Avoid random spacing values; follow Tailwind defaults only.

- Responsive behavior:
  - On smaller screens, sidebar can collapse or become a drawer.
  - Content should stack vertically with proper spacing.

---

### 2. Components (shadcn/ui)

You **must use shadcn/ui components** wherever possible:

- **Navigation**
  - Sidebar with grouped nav items, icons (Lucide), and active state.
  - Top bar with page title, optional description, and actions.

- **Cards & Panels**
  - Use `<Card>`, `<CardHeader>`, `<CardTitle>`, `<CardDescription>`, `<CardContent>`, `<CardFooter>`.
  - Group related information into cards; avoid floating elements.

- **Tables**
  - Use shadcn table patterns for lists (users, teams, billing, logs, etc.).
  - Include:
    - Clear column headers
    - Action column (e.g. “…” dropdown, buttons)
    - Subtle row hover states

- **Forms**
  - Use `<Form>`, `<FormField>`, `<Label>`, `<Input>`, `<Textarea>`, `<Select>`, `<Switch>`.
  - Align labels and inputs consistently (vertical on mobile, optionally horizontal on large screens).
  - Provide clear error messages under inputs.

- **Feedback**
  - Use `<Alert>`, `<Badge>`, `<Tooltip>`, `<Skeleton>` for states.
  - Show loading states (skeletons or spinners) instead of blank screens.

- **Overlays**
  - Use `<Dialog>`, `<Drawer>`, `<DropdownMenu>`, `<Popover>` for secondary actions.
  - Avoid creating new modal implementations; always use shadcn.

---

### 3. Visual Style

- **Colors**
  - Use Tailwind + shadcn tokens, neutral/light theme similar to OpenAI Dashboard.
  - Prefer subtle grayscale with one accent color for primary actions.
  - Avoid flashy gradients and overly saturated colors.

- **Typography**
  - Use the base font from the app (system / Inter-like).
  - Hierarchy:
    - Page titles: larger, bold.
    - Section titles: medium, semi-bold.
    - Descriptions: muted text (`text-muted-foreground`).

- **Borders & Shadows**
  - Favor **subtle** borders (`border`, `border-border`) and soft shadows.
  - Avoid heavy shadows or thick borders.

- **Icons**
  - Use **Lucide** icons via shadcn/ui.
  - Icons should be consistent in size (usually 16–20px) and aligned with text.

---

### 4. OpenAI Platform–Style Patterns

When designing screens, follow these patterns inspired by the OpenAI Platform Dashboard:

- **Dashboard/Home**
  - Grid of cards summarizing key metrics or sections.
  - Some cards may have small tables or list previews.
  - Prominent primary action in top-right of header (e.g. “Create project”, “New key”).

- **Settings / Configuration Pages**
  - Left sidebar for sub-sections (if needed) or tabs at top.
  - Each section in its own card with a clear title and short description.
  - Forms are grouped logically (e.g. “General”, “Billing”, “Team”).

- **List / Index Pages**
  - Main title + description at the top.
  - Primary “Create / New” button.
  - Table or list with:
    - Name / identifier
    - Status badge
    - Last updated / created at
    - Actions (view/edit/delete)

- **Detail Pages**
  - Header with main title, status badge, key metadata.
  - Tabs for related info (Overview, Activity, Settings, etc.).
  - Content organized into cards under each tab.

---

### 5. Rules for Cursor (Very Important)

- **You must:**
  - Use shadcn/ui components for all UI elements.
  - Follow this layout: sidebar + top header + content area.
  - Use consistent spacing, typography, and icon sizes across the app.
  - Provide loading and empty states for major views.

- **You must not:**
  - Introduce other UI frameworks or custom component libraries.
  - Use random Tailwind classes that break the established spacing and layout patterns.
  - Create new visual styles that deviate from the OpenAI Platform–like aesthetic.

- When adding or modifying UI:
  1. Briefly describe the layout and components you’ll use, referencing these guidelines.
  2. Then output the code using shadcn/ui and Tailwind.
  3. Ensure the result fits into the existing app shell and looks consistent with previous screens.
