import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Separator } from '@/components/ui/separator';
import { type BillingData, type Plan, type CreditPack } from '@/types/billing';
import { useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import {
    CreditCard,
    Check,
    Coins,
    ExternalLink,
    Calendar,
    ArrowUp,
} from 'lucide-react';
import { Transition } from '@headlessui/react';

interface BillingSettingsProps {
    currentPlan: Plan | null;
    availablePlans: Plan[];
    creditPacks: CreditPack[];
    subscription: {
        status: string;
        stripe_id?: string | null;
        stripe_status?: string | null;
        next_billing_date?: string | null;
    };
    credits: {
        balance: number;
        monthly_allocation: number;
    };
    stripe_customer_id?: string | null;
}

export default function BillingSettings({
    currentPlan,
    availablePlans,
    creditPacks,
    subscription,
    credits,
    stripe_customer_id,
}: BillingSettingsProps) {
    const [checkoutProcessing, setCheckoutProcessing] = useState(false);
    const [portalProcessing, setPortalProcessing] = useState(false);

    const checkoutForm = useForm({
        plan_key: '',
    });

    const creditPurchaseForm = useForm({
        pack_key: '',
    });

    const portalForm = useForm({});
    const cancelForm = useForm({});

    const handleCheckout = async (planKey: string) => {
        setCheckoutProcessing(true);
        try {
            // Get CSRF token from meta tag
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const response = await fetch('/billing/checkout', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Inertia': 'true',
                    'X-Inertia-Version': document.querySelector('meta[name="inertia-version"]')?.getAttribute('content') || '',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
                body: JSON.stringify({ plan_key: planKey }),
            });

            if (response.ok) {
                const data = await response.json();
                if (data.checkout_url) {
                    window.location.href = data.checkout_url;
                }
            } else {
                const data = await response.json();
                checkoutForm.setError('plan_key', data.message || 'Failed to create checkout session');
                setCheckoutProcessing(false);
            }
        } catch (error) {
            checkoutForm.setError('plan_key', 'An error occurred while processing your request');
            setCheckoutProcessing(false);
        }
    };

    const handleCreditPurchase = (packKey: string) => {
        creditPurchaseForm.setData('pack_key', packKey);
        creditPurchaseForm.post('/billing/credits/purchase', {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({ only: ['auth'] });
            },
        });
    };

    const handleBillingPortal = async () => {
        setPortalProcessing(true);
        try {
            // Get CSRF token from meta tag
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const response = await fetch('/billing/portal', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Inertia': 'true',
                    'X-Inertia-Version': document.querySelector('meta[name="inertia-version"]')?.getAttribute('content') || '',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
            });

            if (response.ok) {
                const data = await response.json();
                if (data.portal_url) {
                    window.location.href = data.portal_url;
                }
            } else {
                const data = await response.json();
                portalForm.setError('portal', data.message || 'Failed to create billing portal session');
                setPortalProcessing(false);
            }
        } catch (error) {
            portalForm.setError('portal', 'An error occurred while processing your request');
            setPortalProcessing(false);
        }
    };

    const handleCancel = () => {
        if (
            confirm(
                'Are you sure you want to cancel your subscription? You will retain access until the end of your billing period.',
            )
        ) {
            cancelForm.post('/billing/cancel', {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload({ only: ['auth'] });
                },
            });
        }
    };

    const isActiveSubscription = subscription.status === 'active';
    const isCanceled = subscription.status === 'canceled';

    return (
        <div className="space-y-6">
            {/* Current Plan & Credits Overview */}
            <div className="space-y-6">
                <HeadingSmall
                    title="Subscription"
                    description="Your current plan and credit balance"
                />

                <div className="space-y-4">
                    {/* Current Plan Info */}
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-1">
                            <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                <CreditCard className="h-3.5 w-3.5" />
                                <span>Current Plan</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <p className="text-sm font-medium">
                                    {currentPlan?.name || 'Free'}
                                </p>
                                {currentPlan?.type === 'paid' && (
                                    <Badge
                                        variant={
                                            isActiveSubscription
                                                ? 'default'
                                                : isCanceled
                                                  ? 'secondary'
                                                  : 'destructive'
                                        }
                                        className="text-xs"
                                    >
                                        {subscription.status}
                                    </Badge>
                                )}
                            </div>
                            {currentPlan && (
                                <p className="text-xs text-muted-foreground">
                                    {currentPlan.monthly_credits} credits/month
                                </p>
                            )}
                        </div>

                        <div className="space-y-1">
                            <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                <Coins className="h-3.5 w-3.5" />
                                <span>Credits Balance</span>
                            </div>
                            <p className="text-sm font-medium">
                                {credits.balance} credits
                            </p>
                            {credits.monthly_allocation > 0 && (
                                <p className="text-xs text-muted-foreground">
                                    {credits.monthly_allocation} credits/month
                                    included
                                </p>
                            )}
                        </div>

                        {subscription.next_billing_date && (
                            <div className="space-y-1">
                                <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                    <Calendar className="h-3.5 w-3.5" />
                                    <span>Next Billing</span>
                                </div>
                                <p className="text-sm font-medium">
                                    {new Date(
                                        subscription.next_billing_date,
                                    ).toLocaleDateString()}
                                </p>
                            </div>
                        )}
                    </div>

                    {/* Subscription Actions */}
                    {currentPlan?.type === 'paid' && (
                        <div className="flex items-center gap-2 pt-2">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={handleBillingPortal}
                                disabled={portalProcessing}
                            >
                                <ExternalLink className="mr-2 h-4 w-4" />
                                Manage Billing
                            </Button>
                            {isActiveSubscription && (
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    onClick={handleCancel}
                                    disabled={cancelForm.processing}
                                >
                                    Cancel Subscription
                                </Button>
                            )}
                        </div>
                    )}
                </div>
            </div>

            <Separator />

            {/* Plan Selection */}
            {availablePlans.length > 0 && (
                <div className="space-y-6">
                    <HeadingSmall
                        title="Change Plan"
                        description="Select a subscription plan that fits your needs"
                    />

                    <RadioGroup
                        value={currentPlan?.key || 'free'}
                        className="space-y-2"
                    >
                        {availablePlans.map((plan) => {
                            const isCurrent = plan.key === currentPlan?.key;
                            const canSelect =
                                !isCurrent && plan.stripe_price_id;

                            return (
                                <div
                                    key={plan.key}
                                    className={`flex items-center justify-between rounded-lg border p-3 transition-colors ${
                                        isCurrent
                                            ? 'border-primary bg-primary/5'
                                            : canSelect
                                              ? 'hover:bg-accent'
                                              : 'opacity-50'
                                    }`}
                                >
                                    <div className="flex items-start space-x-3 flex-1">
                                        <RadioGroupItem
                                            value={plan.key}
                                            id={plan.key}
                                            disabled={!canSelect}
                                            className="mt-0.5"
                                        />
                                        <div className="flex-1 space-y-1.5">
                                            <div className="flex items-center gap-2">
                                                <Label
                                                    htmlFor={plan.key}
                                                    className="font-medium"
                                                >
                                                    {plan.name}
                                                </Label>
                                                {isCurrent && (
                                                    <Badge
                                                        variant="secondary"
                                                        className="text-xs"
                                                    >
                                                        Current
                                                    </Badge>
                                                )}
                                            </div>
                                            <p className="text-sm text-muted-foreground">
                                                {plan.monthly_credits} credits per
                                                month
                                            </p>
                                            {plan.features.length > 0 && (
                                                <div className="flex flex-wrap gap-1.5 pt-0.5">
                                                    {plan.features
                                                        .slice(0, 3)
                                                        .map((feature) => (
                                                            <span
                                                                key={feature}
                                                                className="inline-flex items-center gap-1 rounded bg-muted px-1.5 py-0.5 text-xs"
                                                            >
                                                                <Check className="h-3 w-3 text-green-600" />
                                                                {feature
                                                                    .replace(/_/g, ' ')
                                                                    .replace(
                                                                        /\b\w/g,
                                                                        (l) =>
                                                                            l.toUpperCase(),
                                                                    )}
                                                            </span>
                                                        ))}
                                                    {plan.features.length > 3 && (
                                                        <span className="inline-flex items-center rounded bg-muted px-1.5 py-0.5 text-xs text-muted-foreground">
                                                            +{plan.features.length - 3}
                                                        </span>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                    {canSelect && (
                                        <Button
                                            size="sm"
                                            onClick={() =>
                                                handleCheckout(plan.key)
                                            }
                                            disabled={checkoutProcessing}
                                            className="ml-4"
                                        >
                                            <ArrowUp className="mr-2 h-4 w-4" />
                                            Upgrade
                                        </Button>
                                    )}
                                </div>
                            );
                        })}
                    </RadioGroup>

                    {checkoutProcessing && (
                        <p className="text-sm text-muted-foreground">
                            Processing...
                        </p>
                    )}
                </div>
            )}

            <Separator />

            {/* Buy Credits */}
            {creditPacks.length > 0 && (
                <div className="space-y-6">
                    <HeadingSmall
                        title="Buy Credits"
                        description="Purchase additional credits for AI features"
                    />

                    <div className="space-y-2">
                        {creditPacks.map((pack) => (
                            <div
                                key={pack.key}
                                className="flex items-center justify-between rounded-lg border p-3 transition-colors hover:bg-accent"
                            >
                                <div className="space-y-0.5">
                                    <p className="text-sm font-medium">
                                        {pack.name}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {pack.credits} credits
                                    </p>
                                </div>
                                <Button
                                    size="sm"
                                    onClick={() =>
                                        handleCreditPurchase(pack.key)
                                    }
                                    disabled={
                                        creditPurchaseForm.processing ||
                                        !pack.stripe_price_id
                                    }
                                >
                                    <CreditCard className="mr-2 h-4 w-4" />
                                    Buy
                                </Button>
                            </div>
                        ))}
                    </div>

                    {creditPurchaseForm.processing && (
                        <p className="text-sm text-muted-foreground">
                            Processing...
                        </p>
                    )}
                </div>
            )}
        </div>
    );
}
