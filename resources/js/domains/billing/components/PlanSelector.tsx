import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
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
import { Check, Loader2 } from 'lucide-react';
import { useState } from 'react';
import { type Plan } from '@/types/billing';
import { usePlanChange } from '../hooks/usePlanChange';
import { formatFeatureName } from '../utils/formatters';

interface PlanSelectorProps {
    currentPlan: Plan | null;
    availablePlans: Plan[];
    onSuccess?: (message?: string) => void;
}

export function PlanSelector({ currentPlan, availablePlans, onSuccess }: PlanSelectorProps) {
    const { processing, executePlanChange, getConfirmation } = usePlanChange(onSuccess);
    const [confirmDialog, setConfirmDialog] = useState<{
        open: boolean;
        title: string;
        description: string;
        targetPlan: Plan | null;
    }>({
        open: false,
        title: '',
        description: '',
        targetPlan: null,
    });

    const handlePlanSelect = (plan: Plan) => {
        const confirmation = getConfirmation(currentPlan, plan);
        setConfirmDialog({
            open: true,
            title: confirmation.title,
            description: confirmation.description,
            targetPlan: plan,
        });
    };

    const handleConfirm = () => {
        if (confirmDialog.targetPlan) {
            executePlanChange(confirmDialog.targetPlan);
        }
        setConfirmDialog((prev) => ({ ...prev, open: false }));
    };

    if (availablePlans.length === 0) {
        return null;
    }

    return (
        <>
            <div className="space-y-3">
                {availablePlans.map((plan) => {
                    const isCurrent = plan.key === currentPlan?.key;
                    const canSelect = !isCurrent && plan.stripe_price_id;

                    return (
                        <div
                            key={plan.key}
                            className={`group relative rounded-lg border transition-all ${
                                isCurrent
                                    ? 'border-primary bg-primary/5'
                                    : canSelect
                                      ? 'hover:border-primary/50 hover:bg-accent/50'
                                      : 'opacity-60'
                            }`}
                        >
                            <div className="p-6">
                                <div className="flex items-start justify-between gap-4">
                                    <div className="flex-1 space-y-3">
                                        {/* Plan Name and Badge */}
                                        <div className="flex items-center gap-2">
                                            <h4 className="text-base font-semibold">
                                                {plan.name}
                                            </h4>
                                            {isCurrent && (
                                                <Badge variant="secondary" className="text-xs">
                                                    Current
                                                </Badge>
                                            )}
                                        </div>

                                        {/* Credits */}
                                        <p className="text-sm text-muted-foreground">
                                            {plan.monthly_credits} credits per month
                                        </p>

                                        {/* Features */}
                                        {plan.features.length > 0 && (
                                            <div className="space-y-1.5">
                                                {plan.features.slice(0, 3).map((feature) => (
                                                    <div
                                                        key={feature}
                                                        className="flex items-center gap-2 text-sm text-muted-foreground"
                                                    >
                                                        <Check className="h-4 w-4 text-emerald-600 flex-shrink-0" />
                                                        <span>{formatFeatureName(feature)}</span>
                                                    </div>
                                                ))}
                                                {plan.features.length > 3 && (
                                                    <p className="text-sm text-muted-foreground pl-6">
                                                        +{plan.features.length - 3} more features
                                                    </p>
                                                )}
                                            </div>
                                        )}
                                    </div>

                                    {/* Action Button */}
                                    {canSelect && (
                                        <Button
                                            onClick={() => handlePlanSelect(plan)}
                                            disabled={processing}
                                            size="sm"
                                        >
                                            {processing ? (
                                                <>
                                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                                    Processing
                                                </>
                                            ) : (
                                                'Select plan'
                                            )}
                                        </Button>
                                    )}
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>

            {/* Confirmation Dialog */}
            <AlertDialog
                open={confirmDialog.open}
                onOpenChange={(open) => setConfirmDialog((prev) => ({ ...prev, open }))}
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
                        <AlertDialogAction onClick={handleConfirm}>Confirm</AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}
