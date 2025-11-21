import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { CheckCircle2, AlertTriangle, Calendar, CreditCard, Loader2, X } from 'lucide-react';
import { type Plan } from '@/types/billing';
import { formatDate } from '../utils/formatters';
import { useEffect } from 'react';

interface BillingAlertsProps {
    successMessage: string | null;
    onDismissSuccess: () => void;
    isPastDue: boolean;
    onUpdatePayment: () => void;
    portalProcessing: boolean;
    pendingPlanChange?: {
        plan: Plan;
        scheduled_date: string;
    } | null;
}

export function BillingAlerts({
    successMessage,
    onDismissSuccess,
    isPastDue,
    onUpdatePayment,
    portalProcessing,
    pendingPlanChange,
}: BillingAlertsProps) {
    // Auto-dismiss success message after 5 seconds
    useEffect(() => {
        if (successMessage) {
            const timer = setTimeout(() => {
                onDismissSuccess();
            }, 5000);
            return () => clearTimeout(timer);
        }
    }, [successMessage, onDismissSuccess]);

    return (
        <div className="space-y-4">
            {/* Success Message */}
            {successMessage && (
                <Alert className="border-emerald-200 bg-emerald-50">
                    <CheckCircle2 className="h-4 w-4 text-emerald-600" />
                    <AlertDescription className="text-sm text-emerald-900 pr-8">
                        {successMessage}
                    </AlertDescription>
                    <button
                        onClick={onDismissSuccess}
                        className="absolute right-3 top-3 text-emerald-600 hover:text-emerald-800 transition-colors"
                        aria-label="Dismiss"
                    >
                        <X className="h-4 w-4" />
                    </button>
                </Alert>
            )}

            {/* Past Due Warning */}
            {isPastDue && (
                <Alert className="border-red-200 bg-red-50">
                    <AlertTriangle className="h-5 w-5 text-red-600" />
                    <AlertDescription>
                        <div className="space-y-3">
                            <div className="space-y-1">
                                <p className="text-sm font-medium text-red-900">
                                    Payment Failed - Action Required
                                </p>
                                <p className="text-sm text-red-800">
                                    Your subscription payment is past due. Please update your payment
                                    method to avoid losing access to your subscription features.
                                </p>
                            </div>
                            <Button
                                variant="destructive"
                                size="sm"
                                onClick={onUpdatePayment}
                                disabled={portalProcessing}
                            >
                                {portalProcessing ? (
                                    <>
                                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                        Loading...
                                    </>
                                ) : (
                                    <>
                                        <CreditCard className="mr-2 h-4 w-4" />
                                        Update Payment Method
                                    </>
                                )}
                            </Button>
                        </div>
                    </AlertDescription>
                </Alert>
            )}

            {/* Pending Plan Change Alert */}
            {pendingPlanChange && (
                <Alert className="border-blue-200 bg-blue-50">
                    <Calendar className="h-4 w-4 text-blue-600" />
                    <AlertDescription>
                        <div className="space-y-1">
                            <p className="text-sm font-medium text-blue-900">
                                Scheduled Plan Change
                            </p>
                            <p className="text-sm text-blue-800">
                                Your plan will change to{' '}
                                <span className="font-medium">{pendingPlanChange.plan.name}</span> on{' '}
                                {formatDate(pendingPlanChange.scheduled_date)}. You'll keep your
                                current features until then.
                            </p>
                        </div>
                    </AlertDescription>
                </Alert>
            )}
        </div>
    );
}
