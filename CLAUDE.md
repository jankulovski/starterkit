# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### Quick Start (Docker)
```bash
# Start environment
docker compose up -d

# Full setup (first time)
docker compose exec app composer setup

# Development server with hot reload
docker compose exec app composer dev
# Runs 4 concurrent processes: Laravel server (8000) + Queue worker + Pail logs + Vite dev (5173)

# Development with SSR
docker compose exec app composer dev:ssr
```

### Testing
```bash
# Run all tests
docker compose exec app composer test

# Run specific test file
docker compose exec app php artisan test --filter=TestClassName

# Run specific test method
docker compose exec app php artisan test --filter=TestClassName::test_method_name
```

### Common Artisan Commands
```bash
# Database
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
docker compose exec app php artisan tinker

# Queue (for async jobs like emails)
docker compose exec app php artisan queue:listen --tries=1

# Logs (real-time)
docker compose exec app php artisan pail

# SSR server (production)
docker compose exec app php artisan inertia:start-ssr
```

### Frontend Commands
```bash
# Development server (standalone)
npm run dev

# Build for production
npm run build

# Build with SSR
npm run build:ssr

# Code quality
npm run lint          # ESLint with auto-fix
npm run format        # Prettier formatting
npm run types         # TypeScript type checking
```

### Creating Admin Users
Since password-based login is disabled, you must either:
1. Use Google OAuth with a Google account you control
2. Request a Magic Link for the email
3. Manually set `is_admin = true` via tinker:
```bash
docker compose exec app php artisan tinker
$user = App\Domain\Users\Models\User::where('email', 'your@email.com')->first();
$user->is_admin = true;
$user->save();
```

## Architecture Overview

### Domain-Driven Structure (CRITICAL)

This codebase uses **"domain over layer"** organization. All code related to a business feature lives together, regardless of technical layer.

**Backend domains** (`app/Domain/`):
- `Auth/` - Google OAuth + Magic Link authentication
- `Admin/` - Admin panel, user management, suspension
- `Billing/` - Subscriptions, credits, Stripe integration
- `Settings/` - User profile and preferences
- `Teams/` - Team/workspace functionality
- `Users/` - User model and shared logic

**Frontend domains** (`resources/js/domains/`):
- Mirror backend domains with pages/ and components/

Each domain contains:
- **Controllers/** - HTTP handlers
- **Actions/** - Single-purpose business logic classes (preferred over services)
- **Services/** - Reusable cross-action logic
- **Models/** - Eloquent models
- **Traits/** - Shared behavior (e.g., HasCredits, HasSubscription)
- **Policies/** - Authorization rules
- **Middleware/** - Domain-specific access control

### Inertia.js Integration

**No REST API** - Controllers return Inertia responses, not JSON:
```php
// Controller
return Inertia::render('domains/admin/pages/users/index', [
    'users' => $users,
]);
```

**Page Resolution** - Custom resolver checks domains first:
1. `resources/js/domains/{path}.tsx`
2. `resources/js/pages/{path}.tsx`

**Data Flow**:
- Backend passes data as props via `Inertia::render()`
- React components receive props automatically
- Forms submit via Inertia router (no fetch/axios)
- Shared data in `HandleInertiaRequests` middleware

**State Management**: No Redux/Zustand needed - use Inertia's built-in patterns.

### Authentication Architecture

**Passwordless Login Only** - Two methods:

1. **Google OAuth** (Laravel Socialite)
   - Flow: `/auth/google` → Google consent → `/auth/google/callback`
   - Action: `ResolveUserFromGoogle` auto-creates or finds user
   - Config: `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`

2. **Magic Link** (Custom)
   - Flow: Email input → Generate signed token → Send email → Click link → Verify → Login
   - Security: 15-minute expiration, single-use, SHA-256 hashing
   - Table: `magic_link_tokens` with `used_at` tracking
   - Action: `SendMagicLink`, `VerifyMagicLink`

**User Suspension**: Users can be suspended via `suspended_at` timestamp. Suspended users are silently blocked from all auth methods.

**Important**: Email+password login is DISABLED. Users still have a password field (Laravel requirement) but cannot login with it.

### Billing Architecture - Hybrid Model

**Configuration-Driven Plans** (`config/plans.php`):
```php
'pro' => [
    'key' => 'pro',
    'stripe_price_id' => env('STRIPE_PRICE_PRO_MONTHLY'),
    'monthly_credits' => 200,
    'features' => ['basic_usage', 'advanced_usage', 'priority'],
]
```

**Three Revenue Streams**:

1. **Subscriptions** (Laravel Cashier + Stripe)
   - User-based (not team-based)
   - Plans: Free, Pro, Business
   - Stored in `subscriptions` table
   - User has `current_plan_key` field
   - User model uses `Billable` trait

2. **Credits System** (Custom)
   - Used for metered/AI-heavy operations
   - Sources: Monthly allocation, one-time purchases
   - Tracked in `credit_transactions` table with type field
   - User model uses `HasCredits` trait
   - Methods: `addCredits()`, `chargeCredits()`, `hasCredits()`

3. **Feature Gating** (FeatureGateService)
   - Combines plan check + credit check
   - Defined in `FeatureGateService::$features`
   - Used on both backend (middleware) and frontend (components)

**Webhook Flow**:
```
Stripe event → /stripe/webhook → StripeWebhookController
  → Updates subscription status
  → Allocates monthly credits on renewal
  → Updates user's current_plan_key
```

**Key Files**:
- `app/Domain/Billing/Controllers/BillingController.php` - Checkout, portal, cancel
- `app/Domain/Billing/Controllers/StripeWebhookController.php` - Webhook handlers
- `app/Domain/Billing/Services/PlanService.php` - Plan queries
- `app/Domain/Billing/Services/CreditService.php` - Credit management
- `app/Domain/Billing/Services/FeatureGateService.php` - Access control
- `resources/js/domains/billing/components/` - Frontend paywalls and gates

### Admin System

**Access Control**:
- Backend: `EnsureUserIsAdmin` middleware checks `is_admin` boolean
- Frontend: Conditional navigation + 403 on unauthorized access
- Protected against removing last admin

**Features**:
- User list with search/pagination (DataTable component)
- User detail view
- Edit user (name editable, email immutable)
- Toggle admin status
- Suspend/unsuspend users

**Important**: Email addresses are IMMUTABLE because they're tied to Stripe customer identity.

### Actions Pattern

**Preferred over traditional service classes**. Each action does ONE thing:
```php
// app/Domain/Auth/Actions/SendMagicLink.php
class SendMagicLink
{
    public function execute(string $email): void
    {
        // Generate token, store in DB, send email
    }
}
```

Use actions for:
- Complex business logic
- Multi-step operations
- Operations that need to be reused across controllers

Use services for:
- Cross-action utilities
- Query builders
- Integration wrappers

## Important Conventions

### Naming Conventions

**Backend**:
- Controllers: `{Resource}Controller` (UserController, BillingController)
- Actions: Verb + object (SendMagicLink, VerifyMagicLink, ResolveUserFromGoogle)
- Services: `{Domain}Service` (PlanService, CreditService, FeatureGateService)
- Traits: `Has{Capability}` (HasCredits, HasSubscription)

**Frontend**:
- Pages: Lowercase with path (login, admin/users/index)
- Components: PascalCase (PlanPaywall, FeatureGate, CreditBalance)
- Layouts: `{Name}Layout` (AppLayout, AuthLayout)

### Route Organization

Routes are organized by domain in separate files:
```
routes/
├── web.php       # Core routes + includes
├── admin.php     # Admin routes (prefix: 'admin.')
├── billing.php   # Billing routes
└── settings.php  # Settings routes
```

Routes are grouped with middleware and prefix, then included in web.php.

### Component Library

**shadcn/ui ONLY** - Do not mix other component libraries:
- Components in `resources/js/components/ui/`
- Built on Radix UI primitives
- Styled with Tailwind CSS
- Lucide icons exclusively

**Design System**: OpenAI Platform aesthetic
- Clean, minimal design
- Subtle borders and shadows
- Comfortable whitespace
- See `ui-guidelines.md` for details

## Key Integrations

### Stripe (via Laravel Cashier)

**Setup**:
- User model uses `Billable` trait
- Webhook endpoint: `/stripe/webhook` (must verify signature)
- Customer portal: Managed by Stripe
- Checkout: Stripe Checkout Sessions

**Environment Variables**:
```
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
STRIPE_PRICE_PRO_MONTHLY=
STRIPE_PRICE_BUSINESS_MONTHLY=
```

**Important**:
- Monthly credit allocation happens on `invoice.payment_succeeded` webhook
- Subscription status syncs via `customer.subscription.*` webhooks
- Metadata stores context (plan key, credits amount)

### Google OAuth (via Laravel Socialite)

**Setup**:
1. Google Cloud Console → Create OAuth client
2. Add redirect URI: `http://localhost:8000/auth/google/callback`
3. Set environment variables

**Environment Variables**:
```
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

### Email (Magic Link)

**Development**: Uses log driver or Mailpit (Docker)
**Production**: Configure mail driver in `.env`

Template: `resources/views/emails/magic-link.blade.php`

## Architecture Guidelines

### When Adding New Features

1. **Identify the domain** - Which business area does this belong to?
2. **Create domain folder** - If new domain, create in both backend and frontend
3. **Co-locate related code** - All controllers, actions, models, and components together
4. **Use actions for business logic** - One action = one operation
5. **Update feature gates** - If behind paywall, add to FeatureGateService
6. **Test both layers** - Backend (PHPUnit) and frontend (manual/future E2E)

### When Modifying Billing

**NEVER**:
- Store plan definitions in database
- Let credits go negative
- Skip webhook signature verification
- Change user emails (breaks Stripe customer link)

**ALWAYS**:
- Update `config/plans.php` for plan changes
- Check credits before deduction (`hasCredits()` → `chargeCredits()`)
- Log credit transactions with metadata
- Handle webhook failures gracefully

### When Working with Inertia

**DO**:
- Return Inertia responses from controllers
- Pass data as props
- Use Inertia's form helper for submissions
- Share common data via middleware

**DON'T**:
- Create REST API endpoints for UI
- Use fetch/axios for form submissions
- Store auth state in React (it's server-side)
- Forget to type your page props

## Development Environment

### Docker Architecture

**Single container** with Supervisor managing:
- PHP 8.4 + Node.js 24
- Laravel server (port 8000)
- Vite dev server (port 5173)
- Queue worker
- Pail logs

**Separate containers**:
- PostgreSQL 18 (port 5432)
- Redis 8 (port 6379)

**Why?** Simpler than multi-container PHP/Node setup, faster builds, easier debugging.

### Hot Reload

Vite dev server provides instant HMR for:
- React components
- TypeScript files
- CSS/Tailwind changes

Backend changes require manual refresh (or use Laravel Pail to watch logs).

### Queue System

**Development**: Uses database driver
**Required for**:
- Magic link emails
- Webhook processing
- Any async operations

**Run with**: `composer dev` (includes queue worker) or `php artisan queue:listen --tries=1`

## Common Patterns

### Feature Gating Example

**Backend** (middleware or controller):
```php
if (!FeatureGateService::canAccess(auth()->user(), 'advanced_usage')) {
    return back()->with('error', 'Upgrade to access this feature');
}
```

**Frontend** (component):
```tsx
<FeatureGate feature="advanced_usage" fallback={<PlanPaywall feature="advanced_usage" />}>
  <AdvancedFeatureComponent />
</FeatureGate>
```

### Credit-Based Operations

```php
$user = auth()->user();

// Check before operation
if (!$user->hasCredits(10)) {
    return back()->with('error', 'Insufficient credits');
}

// Perform operation
$result = expensiveOperation();

// Charge credits
$user->chargeCredits(10, [
    'operation' => 'expensive_operation',
    'result_id' => $result->id,
]);
```

### Inertia Page Props Typing

```tsx
// resources/js/domains/admin/pages/users/index.tsx
import { PageProps } from '@/types';

interface Props extends PageProps {
  users: {
    data: Array<{
      id: number;
      name: string;
      email: string;
      is_admin: boolean;
      suspended_at: string | null;
    }>;
    current_page: number;
    last_page: number;
  };
}

export default function UsersIndex({ users }: Props) {
  // Component implementation
}
```

## Testing

### Running Tests

```bash
# All tests
composer test

# Specific file
php artisan test tests/Feature/Billing/SubscriptionTest.php

# Specific method
php artisan test --filter=test_user_can_subscribe_to_plan
```

### Test Structure

Tests are organized by domain:
```
tests/
├── Feature/
│   ├── Auth/
│   ├── Admin/
│   ├── Billing/
│   └── Settings/
└── Unit/
```

### Important Test Patterns

**Billing tests**: Use Stripe test mode and mock webhooks
**Auth tests**: Mock OAuth responses and email sending
**Admin tests**: Protect against removing last admin

## Additional Resources

- **Full documentation**: `docs.md` (1600+ lines)
- **UI guidelines**: `ui-guidelines.md`
- **Project specs**: `specs.md`
- **Laravel docs**: https://laravel.com/docs
- **Inertia docs**: https://inertiajs.com
- **shadcn/ui**: https://ui.shadcn.com
