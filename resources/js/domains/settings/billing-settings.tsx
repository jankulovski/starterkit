import { Separator } from '@/components/ui/separator';
import { type BillingData, type Plan, type CreditPack } from '@/types/billing';
import { useState } from 'react';
import { BillingAlerts } from '@/domains/billing/components/BillingAlerts';
import { CurrentPlanOverview } from '@/domains/billing/components/CurrentPlanOverview';
import { BillingActions } from '@/domains/billing/components/BillingActions';
import { PlanSelector } from '@/domains/billing/components/PlanSelector';
import { CreditPackList } from '@/domains/billing/components/CreditPackList';
import { useSubscriptionActions } from '@/domains/billing/hooks/useSubscriptionActions';

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
    const [successMessage, setSuccessMessage] = useState<string | null>(null);
    const { openBillingPortal, portalProcessing } = useSubscriptionActions();

    const isActiveSubscription = subscription.status === 'active';
    const isCanceled = subscription.status === 'canceled';
    const isPastDue =
        subscription.stripe_status === 'past_due' || subscription.status === 'past_due';
    const hasPaidPlan = currentPlan?.type === 'paid';

    const handleSuccess = (message?: string) => {
        if (message) {
            setSuccessMessage(message);
        }
    };

    return (
        <div className="space-y-8">
            {/* Alerts Section */}
            <BillingAlerts
                successMessage={successMessage}
                onDismissSuccess={() => setSuccessMessage(null)}
                isPastDue={isPastDue}
                onUpdatePayment={openBillingPortal}
                portalProcessing={portalProcessing}
                pendingPlanChange={pendingPlanChange}
            />

            {/* Current Plan & Credits Overview */}
            <div className="space-y-6">
                <div>
                    <h3 className="text-base font-semibold">Subscription</h3>
                    <p className="text-sm text-muted-foreground mt-1">
                        Your current plan and credit balance
                    </p>
                </div>

                <CurrentPlanOverview
                    currentPlan={currentPlan}
                    subscription={subscription}
                    credits={credits}
                />

                <BillingActions
                    hasPaidPlan={hasPaidPlan}
                    isActiveSubscription={isActiveSubscription}
                    isCanceled={isCanceled}
                />
            </div>

            <Separator />

            {/* Plan Selection */}
            {availablePlans.length > 0 && (
                <div className="space-y-6">
                    <div>
                        <h3 className="text-base font-semibold">Available plans</h3>
                        <p className="text-sm text-muted-foreground mt-1">
                            Select a subscription plan that fits your needs
                        </p>
                    </div>

                    <PlanSelector
                        currentPlan={currentPlan}
                        availablePlans={availablePlans}
                        onSuccess={handleSuccess}
                    />
                </div>
            )}

            <Separator />

            {/* Buy Credits */}
            {creditPacks.length > 0 && (
                <div className="space-y-6">
                    <div>
                        <h3 className="text-base font-semibold">Purchase credits</h3>
                        <p className="text-sm text-muted-foreground mt-1">
                            Buy additional credits for AI features
                        </p>
                    </div>

                    <CreditPackList creditPacks={creditPacks} />
                </div>
            )}
        </div>
    );
}
