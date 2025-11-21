import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Separator } from '@/components/ui/separator';
import { Alert, AlertDescription } from '@/components/ui/alert';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { type BillingData, type Plan, type CreditPack } from '@/types/billing';
import { useForm, router } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import {
    CreditCard,
    Check,
    Coins,
    ExternalLink,
    Calendar,
    ArrowUp,
    RotateCcw,
    CheckCircle2,
    Loader2,
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
    pendingPlanChange?: {
        plan: Plan;
        scheduled_date: string;
    } | null;
}

export default function BillingSettings({
    currentPlan,
    availablePlans,
    creditPacks,
    subscription,
    credits,
    stripe_customer_id,
    pendingPlanChange,
}: BillingSettingsProps) {
    const [checkoutProcessing, setCheckoutProcessing] = useState(false);
    const [portalProcessing, setPortalProcessing] = useState(false);
    const [successMessage, setSuccessMessage] = useState<string | null>(null);
    const [confirmDialog, setConfirmDialog] = useState<{
        open: boolean;
        title: string;
        description: string;
        onConfirm: () => void;
    }>({
        open: false,
        title: '',
        description: '',
        onConfirm: () => {},
    });

    const checkoutForm = useForm({
        plan_key: '',
    });

    const creditPurchaseForm = useForm({
        pack_key: '',
    });

    const portalForm = useForm({});
    const cancelForm = useForm({});
    const resumeForm = useForm({});

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
        setConfirmDialog({
            open: true,
            title: 'Cancel subscription?',
            description: 'You will retain access until the end of your billing period.',
            onConfirm: () => {
                cancelForm.post('/billing/cancel', {
                    preserveScroll: true,
                    onSuccess: () => {
                        router.reload();
                    },
                });
                setConfirmDialog((prev) => ({ ...prev, open: false }));
            },
        });
    };

    const handleResume = () => {
        setConfirmDialog({
            open: true,
            title: 'Resume subscription?',
            description: 'You will continue to be billed at the end of your current billing period.',
            onConfirm: () => {
                resumeForm.post('/billing/resume', {
                    preserveScroll: true,
                    onSuccess: () => {
                        router.reload();
                    },
                });
                setConfirmDialog((prev) => ({ ...prev, open: false }));
            },
        });
    };

    const handlePlanChange = (targetPlan: Plan) => {
        const currentCredits = currentPlan?.monthly_credits || 0;
        const targetCredits = targetPlan.monthly_credits || 0;
        const isUpgrade = targetCredits > currentCredits;
        const isDowngrade = targetCredits < currentCredits;

        if (!isUpgrade && !isDowngrade) {
            return; // Same plan
        }

        // Confirmation message based on change type
        const title = isUpgrade ? `Upgrade to ${targetPlan.name}?` : `Switch to ${targetPlan.name}?`;
        const description = isUpgrade
            ? `• You'll be charged a prorated amount now\n• Credits will be reset to ${targetPlan.monthly_credits}\n• New features activate immediately`
            : `• Change takes effect at your next billing date\n• Credits will adjust to ${targetPlan.monthly_credits} then\n• You keep current features until then`;

        setConfirmDialog({
            open: true,
            title,
            description,
            onConfirm: () => {
                executePlanChange(targetPlan);
                setConfirmDialog((prev) => ({ ...prev, open: false }));
            },
        });
    };

    const executePlanChange = async (targetPlan: Plan) => {
        setCheckoutProcessing(true);
        try {
            // Get CSRF token from meta tag
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const response = await fetch('/billing/change-subscription', {
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
                body: JSON.stringify({ plan_key: targetPlan.key }),
            });

            if (response.ok) {
                const data = await response.json();

                // If upgrading from free, redirect to checkout
                if (data.checkout_url) {
                    window.location.href = data.checkout_url;
                } else {
                    // Show success message and reload
                    if (data.message) {
                        setSuccessMessage(data.message);
                    }
                    // Reload the page to show updated plan
                    router.reload();
                    setCheckoutProcessing(false);
                }
            } else {
                const data = await response.json().catch(() => ({ message: 'Failed to change plan' }));
                checkoutForm.setError('plan_key', data.message || 'Failed to change plan');
                setCheckoutProcessing(false);
            }
        } catch (error) {
            checkoutForm.setError('plan_key', 'An error occurred while processing your request');
            setCheckoutProcessing(false);
        }
    };

    const isActiveSubscription = subscription.status === 'active';
    const isCanceled = subscription.status === 'canceled';

    // Auto-dismiss success message after 5 seconds
    useEffect(() => {
        if (successMessage) {
            const timer = setTimeout(() => {
                setSuccessMessage(null);
            }, 5000);
            return () => clearTimeout(timer);
        }
    }, [successMessage]);

    return (
        <div className="space-y-6">
            {/* Success Message */}
            <Transition
                show={!!successMessage}
                enter="transition-all duration-300 ease-out"
                enterFrom="opacity-0 -translate-y-2"
                enterTo="opacity-100 translate-y-0"
                leave="transition-all duration-200 ease-in"
                leaveFrom="opacity-100 translate-y-0"
                leaveTo="opacity-0 -translate-y-2"
            >
                <div>
                    {successMessage && (
                        <Alert className="bg-green-50 border-green-200 relative">
                            <CheckCircle2 className="h-4 w-4 text-green-600" />
                            <AlertDescription className="text-green-800 pr-8">
                                {successMessage}
                            </AlertDescription>
                            <button
                                onClick={() => setSuccessMessage(null)}
                                className="absolute right-3 top-3 text-green-600 hover:text-green-800"
                            >
                                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </Alert>
                    )}
                </div>
            </Transition>

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

                    {/* Pending Plan Change Alert */}
                    {pendingPlanChange && (
                        <div className="rounded-lg border border-blue-200 bg-blue-50 p-3">
                            <div className="flex items-start gap-2">
                                <Calendar className="h-4 w-4 text-blue-600 mt-0.5" />
                                <div className="flex-1 space-y-1">
                                    <p className="text-sm font-medium text-blue-900">
                                        Scheduled Plan Change
                                    </p>
                                    <p className="text-xs text-blue-700">
                                        Your plan will change to{' '}
                                        <strong>{pendingPlanChange.plan.name}</strong>{' '}
                                        on{' '}
                                        {new Date(
                                            pendingPlanChange.scheduled_date,
                                        ).toLocaleDateString()}
                                        . You'll keep your current features until then.
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

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
                                    {cancelForm.processing ? (
                                        <>
                                            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                            Canceling...
                                        </>
                                    ) : (
                                        'Cancel Subscription'
                                    )}
                                </Button>
                            )}
                            {isCanceled && (
                                <Button
                                    variant="default"
                                    size="sm"
                                    onClick={handleResume}
                                    disabled={resumeForm.processing}
                                >
                                    {resumeForm.processing ? (
                                        <>
                                            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                            Resuming...
                                        </>
                                    ) : (
                                        <>
                                            <RotateCcw className="mr-2 h-4 w-4" />
                                            Resume Subscription
                                        </>
                                    )}
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
                                                handlePlanChange(plan)
                                            }
                                            disabled={checkoutProcessing}
                                            className="ml-4"
                                        >
                                            {checkoutProcessing ? (
                                                <>
                                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                                    Processing
                                                </>
                                            ) : (
                                                <>
                                                    <Check className="mr-2 h-4 w-4" />
                                                    Select
                                                </>
                                            )}
                                        </Button>
                                    )}
                                </div>
                            );
                        })}
                    </RadioGroup>
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

            {/* Confirm Dialog */}
            <AlertDialog
                open={confirmDialog.open}
                onOpenChange={(open) =>
                    setConfirmDialog((prev) => ({ ...prev, open }))
                }
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>{confirmDialog.title}</AlertDialogTitle>
                        <AlertDialogDescription className="whitespace-pre-line">
                            {confirmDialog.description}
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction onClick={confirmDialog.onConfirm}>
                            Confirm
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    );
}
