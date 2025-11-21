import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import axios from 'axios';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { AlertTriangle, TrendingDown } from 'lucide-react';

interface DowngradeWarningModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    targetPlanKey: string;
    onConfirm?: () => void;
}

interface DowngradePreview {
    current_plan: {
        key: string;
        name: string;
        tier: number;
    };
    target_plan: {
        key: string;
        name: string;
        tier: number;
        monthly_credits: number;
    };
    credits: {
        current_balance: number;
        new_plan_allocation: number;
        will_be_lost: number;
    };
    effective_date: string | null;
    is_downgrade: boolean;
}

export function DowngradeWarningModal({
    open,
    onOpenChange,
    targetPlanKey,
    onConfirm,
}: DowngradeWarningModalProps) {
    const [preview, setPreview] = useState<DowngradePreview | null>(null);
    const [loading, setLoading] = useState(false);
    const [confirmed, setConfirmed] = useState(false);
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        if (open && targetPlanKey) {
            fetchPreview();
        } else {
            setConfirmed(false);
            setPreview(null);
        }
    }, [open, targetPlanKey]);

    const fetchPreview = async () => {
        setLoading(true);
        try {
            const response = await axios.post<DowngradePreview>('/billing/preview-downgrade', {
                plan_key: targetPlanKey,
            });
            setPreview(response.data);
        } catch (error) {
            console.error('Failed to fetch downgrade preview:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleConfirm = () => {
        if (!confirmed || !preview) return;

        setSubmitting(true);

        if (onConfirm) {
            onConfirm();
        } else {
            // Default: Submit the subscription change
            router.post(
                '/billing/change-subscription',
                { plan_key: targetPlanKey },
                {
                    onFinish: () => {
                        setSubmitting(false);
                        onOpenChange(false);
                    },
                }
            );
        }
    };

    const formatDate = (dateString: string | null) => {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <TrendingDown className="h-5 w-5 text-orange-500" />
                        Confirm Downgrade
                    </DialogTitle>
                    <DialogDescription>
                        Please review the changes that will occur when you downgrade your plan.
                    </DialogDescription>
                </DialogHeader>

                {loading ? (
                    <div className="py-8 text-center text-muted-foreground">Loading preview...</div>
                ) : preview ? (
                    <div className="space-y-4">
                        {/* Plan change summary */}
                        <div className="rounded-lg border bg-muted/50 p-4">
                            <div className="mb-2 text-sm font-medium">Plan Change</div>
                            <div className="flex items-center justify-between text-sm">
                                <span>
                                    {preview.current_plan.name}{' '}
                                    <span className="text-muted-foreground">(Tier {preview.current_plan.tier})</span>
                                </span>
                                <span className="text-muted-foreground">→</span>
                                <span className="font-medium">
                                    {preview.target_plan.name}{' '}
                                    <span className="text-muted-foreground">(Tier {preview.target_plan.tier})</span>
                                </span>
                            </div>
                        </div>

                        {/* Credit loss warning */}
                        {preview.credits.will_be_lost > 0 && (
                            <Alert variant="destructive">
                                <AlertTriangle className="h-4 w-4" />
                                <AlertDescription className="ml-6">
                                    <strong>You will lose {preview.credits.will_be_lost} credits</strong>
                                    <div className="mt-2 space-y-1 text-sm">
                                        <div>Current balance: {preview.credits.current_balance} credits</div>
                                        <div>New plan allocation: {preview.credits.new_plan_allocation} credits</div>
                                        <div className="font-medium text-destructive">
                                            Credits to be removed: {preview.credits.will_be_lost}
                                        </div>
                                    </div>
                                </AlertDescription>
                            </Alert>
                        )}

                        {/* No credit loss message */}
                        {preview.credits.will_be_lost === 0 && (
                            <div className="rounded-lg border bg-green-50 p-4 dark:bg-green-950">
                                <div className="text-sm text-green-900 dark:text-green-100">
                                    No credits will be lost. Your current balance of {preview.credits.current_balance}{' '}
                                    credits is within the new plan's allocation.
                                </div>
                            </div>
                        )}

                        {/* Effective date */}
                        <div className="text-sm text-muted-foreground">
                            <span className="font-medium">Effective date:</span>{' '}
                            {preview.effective_date ? formatDate(preview.effective_date) : 'End of billing period'}
                        </div>

                        {/* Confirmation checkbox */}
                        <div className="flex items-start space-x-2 rounded-lg border p-4">
                            <Checkbox
                                id="confirm-downgrade"
                                checked={confirmed}
                                onCheckedChange={(checked) => setConfirmed(checked === true)}
                            />
                            <label
                                htmlFor="confirm-downgrade"
                                className="text-sm leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                            >
                                I understand that this will downgrade my plan
                                {preview.credits.will_be_lost > 0 &&
                                    ` and I will lose ${preview.credits.will_be_lost} credits`}
                                .
                            </label>
                        </div>
                    </div>
                ) : (
                    <div className="py-8 text-center text-muted-foreground">Failed to load preview</div>
                )}

                <DialogFooter>
                    <Button variant="outline" onClick={() => onOpenChange(false)} disabled={submitting}>
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        onClick={handleConfirm}
                        disabled={!confirmed || loading || submitting}
                    >
                        {submitting ? 'Processing...' : 'Confirm Downgrade'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
