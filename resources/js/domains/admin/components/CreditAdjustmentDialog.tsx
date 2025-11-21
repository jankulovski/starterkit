import { useState } from 'react';
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
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Coins, Plus, Minus, AlertCircle } from 'lucide-react';
import { toast } from 'sonner';

interface CreditAdjustmentDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    user: {
        id: number;
        name: string;
        email: string;
        credits_balance: number;
    };
    onSuccess?: () => void;
}

export function CreditAdjustmentDialog({ open, onOpenChange, user, onSuccess }: CreditAdjustmentDialogProps) {
    const [amount, setAmount] = useState<string>('');
    const [reason, setReason] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError(null);

        const numericAmount = parseInt(amount, 10);

        if (isNaN(numericAmount) || numericAmount === 0) {
            setError('Please enter a valid non-zero amount');
            return;
        }

        if (!reason.trim() || reason.trim().length < 10) {
            setError('Reason must be at least 10 characters');
            return;
        }

        setSubmitting(true);

        try {
            const response = await axios.post('/admin/credits/adjust', {
                user_id: user.id,
                amount: numericAmount,
                reason: reason.trim(),
            });

            toast.success(response.data.message, {
                description: `New balance: ${response.data.new_balance} credits`,
            });

            // Reset form
            setAmount('');
            setReason('');
            onOpenChange(false);

            // Callback for parent to refresh data
            if (onSuccess) {
                onSuccess();
            }
        } catch (err: any) {
            const errorMessage = err.response?.data?.message || 'Failed to adjust credits';
            setError(errorMessage);
            toast.error('Failed to adjust credits', {
                description: errorMessage,
            });
        } finally {
            setSubmitting(false);
        }
    };

    const numericAmount = parseInt(amount, 10);
    const isAddition = !isNaN(numericAmount) && numericAmount > 0;
    const isDeduction = !isNaN(numericAmount) && numericAmount < 0;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <Coins className="h-5 w-5" />
                        Adjust Credits
                    </DialogTitle>
                    <DialogDescription>
                        Manually add or deduct credits for <strong>{user.name}</strong> ({user.email})
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    {/* Current Balance */}
                    <div className="rounded-lg border bg-muted/50 p-4">
                        <div className="text-sm text-muted-foreground">Current Balance</div>
                        <div className="text-2xl font-bold">{user.credits_balance} credits</div>
                    </div>

                    {/* Amount Input */}
                    <div className="space-y-2">
                        <Label htmlFor="amount">
                            Amount <span className="text-muted-foreground">(positive to add, negative to deduct)</span>
                        </Label>
                        <div className="relative">
                            <Input
                                id="amount"
                                type="number"
                                value={amount}
                                onChange={(e) => setAmount(e.target.value)}
                                placeholder="e.g., 100 or -50"
                                className="pr-10"
                                required
                            />
                            <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                {isAddition && <Plus className="h-4 w-4 text-green-500" />}
                                {isDeduction && <Minus className="h-4 w-4 text-red-500" />}
                            </div>
                        </div>
                        {numericAmount !== 0 && !isNaN(numericAmount) && (
                            <p className="text-sm text-muted-foreground">
                                New balance will be:{' '}
                                <span className="font-medium">{user.credits_balance + numericAmount} credits</span>
                            </p>
                        )}
                    </div>

                    {/* Reason Input */}
                    <div className="space-y-2">
                        <Label htmlFor="reason">
                            Reason <span className="text-muted-foreground">(min 10 characters)</span>
                        </Label>
                        <Textarea
                            id="reason"
                            value={reason}
                            onChange={(e) => setReason(e.target.value)}
                            placeholder="Explain why you're adjusting credits (e.g., compensation for service outage, test credit removal, etc.)"
                            rows={3}
                            required
                            minLength={10}
                            maxLength={500}
                        />
                        <p className="text-xs text-muted-foreground">{reason.length}/500 characters</p>
                    </div>

                    {/* Warning for deductions */}
                    {isDeduction && Math.abs(numericAmount) > user.credits_balance && (
                        <Alert variant="destructive">
                            <AlertCircle className="h-4 w-4" />
                            <AlertDescription>
                                User only has {user.credits_balance} credits. Cannot deduct {Math.abs(numericAmount)}.
                            </AlertDescription>
                        </Alert>
                    )}

                    {/* Error Display */}
                    {error && (
                        <Alert variant="destructive">
                            <AlertCircle className="h-4 w-4" />
                            <AlertDescription>{error}</AlertDescription>
                        </Alert>
                    )}

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={submitting}>
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            disabled={
                                submitting ||
                                !amount ||
                                parseInt(amount) === 0 ||
                                !reason.trim() ||
                                reason.trim().length < 10
                            }
                        >
                            {submitting ? 'Adjusting...' : isAddition ? 'Add Credits' : 'Deduct Credits'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
