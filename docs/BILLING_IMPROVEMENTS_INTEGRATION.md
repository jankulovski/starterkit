# Billing Improvements - Integration Guide

This guide shows you how to integrate the new billing improvements into your application.

---

## 1. Downgrade Warning Modal

### When to Use
Show this modal when a user attempts to downgrade their subscription to warn them about credit loss.

### Basic Integration

```tsx
// In your billing settings page
import { useState } from 'react';
import { DowngradeWarningModal } from '@/domains/billing/components/DowngradeWarningModal';
import { Button } from '@/components/ui/button';

export function BillingSettings() {
    const [showDowngradeModal, setShowDowngradeModal] = useState(false);
    const [selectedPlanKey, setSelectedPlanKey] = useState<string>('');

    const handlePlanChange = (planKey: string) => {
        const currentPlan = getCurrentPlanTier();
        const targetPlan = getPlanTier(planKey);

        // Only show modal for downgrades
        if (targetPlan < currentPlan) {
            setSelectedPlanKey(planKey);
            setShowDowngradeModal(true);
        } else {
            // Direct upgrade without warning
            router.post('/billing/change-subscription', { plan_key: planKey });
        }
    };

    return (
        <>
            <div>
                {/* Your plan selection UI */}
                <Button onClick={() => handlePlanChange('free')}>
                    Downgrade to Free
                </Button>
            </div>

            {/* Downgrade Warning Modal */}
            <DowngradeWarningModal
                open={showDowngradeModal}
                onOpenChange={setShowDowngradeModal}
                targetPlanKey={selectedPlanKey}
            />
        </>
    );
}
```

### Advanced: Custom Confirmation Handler

```tsx
<DowngradeWarningModal
    open={showDowngradeModal}
    onOpenChange={setShowDowngradeModal}
    targetPlanKey={selectedPlanKey}
    onConfirm={() => {
        // Custom logic before downgrade
        router.post('/billing/change-subscription',
            { plan_key: selectedPlanKey },
            {
                onSuccess: () => {
                    toast.success('Plan downgraded successfully');
                    setShowDowngradeModal(false);
                },
                onError: () => {
                    toast.error('Failed to downgrade plan');
                }
            }
        );
    }}
/>
```

### API: Preview Downgrade Endpoint

```tsx
// Fetch preview data manually (modal does this automatically)
const response = await axios.post<DowngradePreview>('/billing/preview-downgrade', {
    plan_key: 'free',
});

console.log(response.data.credits.will_be_lost); // Credits that will be removed
```

---

## 2. Admin Credit Adjustment

### When to Use
- User reports incorrect credits
- Compensation for service outage
- Test credit removal
- Bonus credit grants

### Basic Integration in Admin Panel

```tsx
// In admin user detail page
import { useState } from 'react';
import { CreditAdjustmentDialog } from '@/domains/admin/components/CreditAdjustmentDialog';
import { Button } from '@/components/ui/button';

export function AdminUserDetail({ user }: { user: User }) {
    const [showCreditDialog, setShowCreditDialog] = useState(false);

    const handleCreditAdjustmentSuccess = () => {
        // Refresh user data
        router.reload({ only: ['user'] });
    };

    return (
        <div>
            {/* User details */}
            <div>
                <h3>Credit Balance</h3>
                <p>{user.credits_balance} credits</p>
                <Button
                    variant="outline"
                    onClick={() => setShowCreditDialog(true)}
                >
                    Adjust Credits
                </Button>
            </div>

            {/* Credit Adjustment Dialog */}
            <CreditAdjustmentDialog
                open={showCreditDialog}
                onOpenChange={setShowCreditDialog}
                user={{
                    id: user.id,
                    name: user.name,
                    email: user.email,
                    credits_balance: user.credits_balance,
                }}
                onSuccess={handleCreditAdjustmentSuccess}
            />
        </div>
    );
}
```

### API: Manual Credit Adjustment

```tsx
// Add credits (positive amount)
await axios.post('/admin/credits/adjust', {
    user_id: 123,
    amount: 50,
    reason: 'Compensation for service outage on 2025-01-15',
});

// Deduct credits (negative amount)
await axios.post('/admin/credits/adjust', {
    user_id: 123,
    amount: -25,
    reason: 'Removing test credits accidentally granted',
});
```

### API: Fetch Credit History

```tsx
const response = await axios.get('/admin/credits/history', {
    params: {
        user_id: 123,
        limit: 50, // optional, defaults to 50
    },
});

console.log(response.data.transactions);
// [
//   {
//     id: 1,
//     amount: 50,
//     type: 'admin_grant',
//     description: 'Admin grant: Compensation...',
//     balance_after: 150,
//     metadata: { adjusted_by: 1, adjusted_by_email: 'admin@example.com' },
//     created_at: '2025-01-15T10:30:00Z',
//   },
//   ...
// ]
```

---

## 3. Invoice List

### Basic Integration

```tsx
// In billing settings/dashboard page
import { InvoiceList } from '@/domains/billing/components/InvoiceList';

export function BillingDashboard() {
    return (
        <div>
            {/* Other billing components */}
            <InvoiceList />
        </div>
    );
}
```

### API: Fetch Invoices

```tsx
const response = await axios.get('/billing/invoices');

console.log(response.data.invoices);
// [
//   {
//     id: 'in_123',
//     number: 'INV-2025-001',
//     status: 'paid',
//     amount_paid: 2000, // in cents
//     currency: 'USD',
//     created_at: '2025-01-01 10:00:00',
//     period_start: '2025-01-01',
//     period_end: '2025-01-31',
//     pdf_url: 'https://stripe.com/invoice.pdf',
//     hosted_url: 'https://stripe.com/hosted',
//   },
//   ...
// ]
```

### Download Invoice PDF

```tsx
// Option 1: Direct link (component handles this)
<a href="/billing/invoices/in_123/pdf" target="_blank">
    Download PDF
</a>

// Option 2: Programmatic download
window.open(`/billing/invoices/${invoiceId}/pdf`, '_blank');
```

---

## 4. Refund & Chargeback Handling

### Automatic Processing
The new webhook handlers automatically process refunds and chargebacks. **No frontend integration needed** - it's all backend!

### What Happens Automatically

1. **Refund Received** (`charge.refunded` webhook):
   - Credits are automatically reversed if user has sufficient balance
   - Transaction logged with type `'refund'`
   - If insufficient balance, error logged for manual review

2. **Chargeback Filed** (`charge.dispute.created` webhook):
   - Critical alert logged for investigation
   - Credits NOT reversed yet (wait for dispute outcome)
   - Full dispute details logged

### Monitoring Refunds

```php
// Check for refund transactions
$refundTransactions = CreditTransaction::where('type', 'refund')
    ->where('created_at', '>=', now()->subDays(30))
    ->get();

// Check for insufficient balance errors
Log::where('level', 'error')
    ->where('message', 'like', '%Cannot reverse credits%')
    ->get();
```

---

## 5. Plan Tier Comparison

### Backend Usage

```php
use App\Domain\Billing\Services\PlanService;

$planService = app(PlanService::class);

// Check if upgrade
if ($planService->isUpgrade($currentPlan, $targetPlan)) {
    // Handle upgrade immediately
    $subscription->swapAndInvoice($newPriceId);
}

// Check if downgrade
if ($planService->isDowngrade($currentPlan, $targetPlan)) {
    // Schedule for end of period
    $subscription->noProrate()->swap($newPriceId);
    $user->schedulePlanChange($targetPlan);
}
```

### Frontend Usage

```tsx
// Get plan tiers from config
const plans = {
    free: { tier: 0, ... },
    pro: { tier: 1, ... },
    business: { tier: 2, ... },
};

const isUpgrade = plans[targetPlan].tier > plans[currentPlan].tier;
const isDowngrade = plans[targetPlan].tier < plans[currentPlan].tier;
```

---

## 6. Credit Pack Validation

### Backend Validation (Automatic)
The `purchaseCredits()` endpoint now automatically validates pack_key against config.

### Frontend Validation

```tsx
// Get valid pack keys
const creditPacks = {
    small: { credits: 50, price: '$5' },
    medium: { credits: 100, price: '$9' },
    large: { credits: 500, price: '$40' },
};

const validPackKeys = Object.keys(creditPacks);

// Validate before submission
const handlePurchase = (packKey: string) => {
    if (!validPackKeys.includes(packKey)) {
        toast.error('Invalid credit pack selected');
        return;
    }

    router.post('/billing/credits/purchase', { pack_key: packKey });
};
```

---

## Common Integration Patterns

### Pattern 1: Billing Settings Page

```tsx
export default function BillingSettings() {
    return (
        <div className="space-y-6">
            {/* Current Plan & Subscription Status */}
            <CurrentPlanCard />

            {/* Plan Selection with Downgrade Warning */}
            <PlanSelection />

            {/* Credit Balance & Purchase */}
            <CreditBalance />

            {/* Invoice History */}
            <InvoiceList />
        </div>
    );
}
```

### Pattern 2: Admin User Management

```tsx
export default function AdminUserDetail({ user }) {
    return (
        <div className="space-y-6">
            {/* User Info */}
            <UserInfoCard user={user} />

            {/* Credit Management */}
            <Card>
                <CardHeader>
                    <CardTitle>Credits</CardTitle>
                </CardHeader>
                <CardContent>
                    <p>Balance: {user.credits_balance}</p>
                    <Button onClick={() => setShowCreditDialog(true)}>
                        Adjust Credits
                    </Button>
                </CardContent>
            </Card>

            <CreditAdjustmentDialog {...} />
        </div>
    );
}
```

### Pattern 3: Subscription Change Flow

```tsx
const handleSubscriptionChange = async (newPlanKey: string) => {
    const currentTier = getCurrentPlanTier();
    const targetTier = getPlanTier(newPlanKey);

    // Step 1: Check if downgrade
    if (targetTier < currentTier) {
        // Show warning modal
        setSelectedPlan(newPlanKey);
        setShowDowngradeModal(true);
        return;
    }

    // Step 2: Immediate upgrade
    try {
        await router.post('/billing/change-subscription', {
            plan_key: newPlanKey,
        });

        toast.success('Plan upgraded successfully!');
    } catch (error) {
        toast.error('Failed to upgrade plan');
    }
};
```

---

## Testing Integrations

### Test Refund Handling

```bash
# Using Stripe CLI
stripe trigger charge.refunded
```

### Test Chargeback

```bash
# Using Stripe CLI
stripe trigger charge.dispute.created
```

### Test Downgrade Preview

```bash
# Using Tinker
php artisan tinker

$user = User::first();
$user->update(['current_plan_key' => 'business', 'credits_balance' => 500]);

# Then in browser, attempt to downgrade to see modal
```

---

## Troubleshooting

### Downgrade Modal Not Showing Preview

**Problem**: Modal shows "Loading preview..." indefinitely

**Solutions**:
1. Check browser console for API errors
2. Verify `/billing/preview-downgrade` route is registered
3. Ensure user has active subscription
4. Check Laravel logs for backend errors

### Admin Can't Adjust Credits

**Problem**: "403 Forbidden" when adjusting credits

**Solutions**:
1. Verify user has `is_admin = true` in database
2. Check middleware is applied to admin routes
3. Verify admin routes are wrapped in auth middleware

### Invoices Not Loading

**Problem**: Empty invoice list or error message

**Solutions**:
1. Verify user has `stripe_id` set
2. Check Stripe API keys are configured
3. Verify webhook secret is correct
4. Check Laravel logs for Stripe API errors

### Refunds Not Reversing Credits

**Problem**: Refund webhook received but credits not reversed

**Solutions**:
1. Check `charge_id` is stored in transaction metadata
2. Verify webhook signature is valid
3. Check logs for "Cannot reverse credits" error
4. Ensure idempotency table exists and is working

---

## Performance Considerations

### Downgrade Preview
- **Cached**: No - preview is calculated in real-time
- **Response time**: ~100-200ms
- **Rate limit**: 20 requests/minute

### Invoice List
- **Cached**: No - fetched from Stripe API
- **Response time**: ~500-1000ms (Stripe API latency)
- **Rate limit**: 20 requests/minute
- **Optimization**: Consider caching for 5 minutes if needed

### Admin Credit Adjustment
- **Cached**: No
- **Response time**: ~50-100ms
- **Rate limit**: 20 requests/minute
- **Database transactions**: Yes (prevents race conditions)

---

## Security Best Practices

1. **Never expose Stripe secret keys** in frontend code
2. **Always verify webhook signatures** (handled automatically)
3. **Validate plan_key and pack_key** against config (handled automatically)
4. **Check admin permissions** before allowing credit adjustments
5. **Verify invoice ownership** before allowing downloads (handled automatically)
6. **Log all admin actions** for audit trail (handled automatically)
7. **Use rate limiting** on all billing endpoints (configured)

---

## Next Steps

1. **Implement in UI**: Add components to your billing settings page
2. **Test thoroughly**: Use Stripe test mode and webhook CLI
3. **Monitor logs**: Watch for refund/chargeback alerts
4. **Train admins**: Show admin panel credit adjustment tools
5. **Update documentation**: Add to your internal docs

Need help? Check the test files for comprehensive examples of all features!
