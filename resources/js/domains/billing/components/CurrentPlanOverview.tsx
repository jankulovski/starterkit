import { Badge } from '@/components/ui/badge';
import { CreditCard, Coins, Calendar } from 'lucide-react';
import { type Plan } from '@/types/billing';
import { formatDate, formatCredits } from '../utils/formatters';

interface CurrentPlanOverviewProps {
    currentPlan: Plan | null;
    subscription: {
        status: string;
        next_billing_date?: string | null;
    };
    credits: {
        balance: number;
        monthly_allocation: number;
    };
}

export function CurrentPlanOverview({
    currentPlan,
    subscription,
    credits,
}: CurrentPlanOverviewProps) {
    const isActiveSubscription = subscription.status === 'active';
    const isCanceled = subscription.status === 'canceled';

    const getStatusVariant = () => {
        if (isActiveSubscription) return 'default';
        if (isCanceled) return 'secondary';
        return 'destructive';
    };

    return (
        <div className="space-y-2">
            <div className="grid gap-6 sm:grid-cols-2">
                {/* Current Plan */}
                <div className="space-y-2">
                    <label className="text-sm font-medium text-muted-foreground">
                        Current plan
                    </label>
                    <div className="flex items-center gap-2">
                        <p className="text-base font-medium">
                            {currentPlan?.name || 'Free'}
                        </p>
                        {currentPlan?.type === 'paid' && (
                            <Badge variant={getStatusVariant()} className="text-xs capitalize">
                                {subscription.status}
                            </Badge>
                        )}
                    </div>
                    {currentPlan && (
                        <p className="text-sm text-muted-foreground">
                            {currentPlan.monthly_credits} credits per month
                        </p>
                    )}
                </div>

                {/* Credits Balance */}
                <div className="space-y-2">
                    <label className="text-sm font-medium text-muted-foreground">
                        Credits balance
                    </label>
                    <p className="text-base font-medium">
                        {formatCredits(credits.balance)}
                    </p>
                    {credits.monthly_allocation > 0 && (
                        <p className="text-sm text-muted-foreground">
                            {formatCredits(credits.monthly_allocation)} included monthly
                        </p>
                    )}
                </div>

                {/* Next Billing Date */}
                {subscription.next_billing_date && (
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-muted-foreground">
                            Next billing date
                        </label>
                        <p className="text-base font-medium">
                            {formatDate(subscription.next_billing_date)}
                        </p>
                    </div>
                )}
            </div>
        </div>
    );
}
