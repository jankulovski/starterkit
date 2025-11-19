import * as React from 'react';
import { router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/input-error';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import { UserInfo } from '@/components/user-info';
import { type User as UserType } from '@/types';
import { Mail, Calendar, CheckCircle2, XCircle, Ban, CheckCircle, CreditCard, Coins } from 'lucide-react';
import { Transition } from '@headlessui/react';

interface User extends UserType {
    is_admin: boolean;
    suspended_at: string | null;
    billing?: {
        credits_balance: number;
        current_plan: {
            key: string;
            name: string;
            type: 'free' | 'paid';
            monthly_credits: number;
            features: string[];
        } | null;
        subscription_status: 'none' | 'active' | 'canceled' | 'expired';
        stripe_customer_id?: string | null;
        next_billing_date?: string | null;
        stripe_subscription_id?: string | null;
    };
}

interface UserEditDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    user: User | null;
    onSuccess?: () => void;
}

export function UserEditDialog({ open, onOpenChange, user, onSuccess }: UserEditDialogProps) {
    const { data, setData, patch, processing, errors, recentlySuccessful } = useForm({
        name: user?.name || '',
        is_admin: user?.is_admin || false,
    });

    // Reset form when user changes
    React.useEffect(() => {
        if (user) {
            setData({
                name: user.name,
                is_admin: user.is_admin,
            });
        }
    }, [user, setData]);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!user) return;

        patch(`/admin/users/${user.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                if (onSuccess) {
                    onSuccess();
                }
            },
        });
    };

    const handleSuspend = () => {
        if (!user) return;
        if (confirm('Are you sure you want to suspend this user? They will not be able to log in.')) {
            router.post(`/admin/users/${user.id}/suspend`, {}, {
                preserveScroll: true,
                onSuccess: () => {
                    if (onSuccess) {
                        onSuccess();
                    }
                },
            });
        }
    };

    const handleUnsuspend = () => {
        if (!user) return;
        router.post(`/admin/users/${user.id}/unsuspend`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                if (onSuccess) {
                    onSuccess();
                }
            },
        });
    };

    if (!user) {
        return null;
    }

    // Use the user prop which will update after successful save
    const displayUser = user;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                <DialogTitle className="sr-only">Edit User: {displayUser.name}</DialogTitle>
                <DialogDescription className="sr-only">
                    User details and settings for {displayUser.name}
                </DialogDescription>
                <DialogHeader>
                    <div className="flex items-center gap-3">
                        <UserInfo user={displayUser} showEmail={true} />
                        <div className="flex items-center gap-2 ml-auto">
                            {user.is_admin && (
                                <Badge variant="default">Admin</Badge>
                            )}
                            {user.suspended_at ? (
                                <Badge variant="destructive">Suspended</Badge>
                            ) : (
                                <Badge variant="secondary">Active</Badge>
                            )}
                        </div>
                    </div>
                </DialogHeader>

                <div className="space-y-6">
                    {/* User Information */}
                    <div className="space-y-4">
                        <h3 className="text-sm font-semibold text-foreground">Account Information</h3>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-1">
                                <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                    <Mail className="h-3.5 w-3.5" />
                                    <span>Email</span>
                                </div>
                                <p className="text-sm font-medium">{user.email}</p>
                                <div className="flex items-center gap-1.5 mt-1">
                                    {user.email_verified_at ? (
                                        <>
                                            <CheckCircle2 className="h-3 w-3 text-green-600" />
                                            <span className="text-xs text-muted-foreground">Verified</span>
                                        </>
                                    ) : (
                                        <>
                                            <XCircle className="h-3 w-3 text-muted-foreground" />
                                            <span className="text-xs text-muted-foreground">Unverified</span>
                                        </>
                                    )}
                                </div>
                            </div>

                            <div className="space-y-1">
                                <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                    <Calendar className="h-3.5 w-3.5" />
                                    <span>Created</span>
                                </div>
                                <p className="text-sm font-medium">
                                    {new Date(user.created_at).toLocaleDateString()}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {new Date(user.created_at).toLocaleTimeString()}
                                </p>
                            </div>

                            {user.suspended_at && (
                                <div className="space-y-1">
                                    <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                        <Ban className="h-3.5 w-3.5" />
                                        <span>Suspended</span>
                                    </div>
                                    <p className="text-sm font-medium">
                                        {new Date(user.suspended_at).toLocaleDateString()}
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>

                    <Separator />

                    {/* Billing Information */}
                    {user.billing && (
                        <>
                            <div className="space-y-4">
                                <h3 className="text-sm font-semibold text-foreground">Billing Information</h3>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-1">
                                        <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                            <CreditCard className="h-3.5 w-3.5" />
                                            <span>Current Plan</span>
                                        </div>
                                        <p className="text-sm font-medium">
                                            {user.billing.current_plan?.name || 'Free'}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {user.billing.current_plan?.type === 'paid' 
                                                ? 'Paid subscription' 
                                                : 'Free plan'}
                                        </p>
                                    </div>

                                    <div className="space-y-1">
                                        <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                            <Coins className="h-3.5 w-3.5" />
                                            <span>Credits Balance</span>
                                        </div>
                                        <p className="text-sm font-medium">
                                            {user.billing.credits_balance}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {user.billing.current_plan?.monthly_credits || 0} credits/month included
                                        </p>
                                    </div>

                                    {user.billing.subscription_status && user.billing.subscription_status !== 'none' && (
                                        <div className="space-y-1">
                                            <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                                <span>Subscription Status</span>
                                            </div>
                                            <Badge 
                                                variant={
                                                    user.billing.subscription_status === 'active' 
                                                        ? 'default' 
                                                        : user.billing.subscription_status === 'canceled'
                                                        ? 'secondary'
                                                        : 'destructive'
                                                }
                                                className="text-xs"
                                            >
                                                {user.billing.subscription_status}
                                            </Badge>
                                        </div>
                                    )}

                                    {user.billing.next_billing_date && (
                                        <div className="space-y-1">
                                            <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                                <Calendar className="h-3.5 w-3.5" />
                                                <span>Next Billing</span>
                                            </div>
                                            <p className="text-sm font-medium">
                                                {new Date(user.billing.next_billing_date).toLocaleDateString()}
                                            </p>
                                        </div>
                                    )}

                                    {user.billing.stripe_customer_id && (
                                        <div className="space-y-1">
                                            <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                                <span>Stripe Customer ID</span>
                                            </div>
                                            <p className="text-sm font-mono text-xs break-all">
                                                {user.billing.stripe_customer_id}
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </div>
                            <Separator />
                        </>
                    )}

                    {/* Edit Form */}
                    <div className="space-y-4">
                        <h3 className="text-sm font-semibold text-foreground">Edit User</h3>
                        <form onSubmit={submit} className="space-y-5">
                            <div className="space-y-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    required
                                    className="max-w-md"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="flex items-start justify-between rounded-lg border p-4">
                                <div className="space-y-1">
                                    <Label htmlFor="is_admin" className="text-sm font-medium">
                                        Administrator
                                    </Label>
                                    <p className="text-xs text-muted-foreground">
                                        Grant admin privileges to this user
                                    </p>
                                </div>
                                <Checkbox
                                    id="is_admin"
                                    checked={data.is_admin}
                                    onCheckedChange={(checked) => setData('is_admin', checked === true)}
                                />
                            </div>
                            <InputError message={errors.is_admin} />

                            <div className="flex items-center gap-3 pt-2">
                                <Button type="submit" disabled={processing}>
                                    Save Changes
                                </Button>
                                <Transition
                                    show={recentlySuccessful}
                                    enter="transition ease-in-out"
                                    enterFrom="opacity-0"
                                    leave="transition ease-in-out"
                                    leaveTo="opacity-0"
                                >
                                    <p className="text-sm text-muted-foreground">
                                        Saved
                                    </p>
                                </Transition>
                            </div>
                        </form>
                    </div>

                    <Separator />

                    {/* Account Actions */}
                    <div className="space-y-4">
                        <h3 className="text-sm font-semibold text-foreground">Account Actions</h3>
                        {user.suspended_at ? (
                            <div className="space-y-3">
                                <p className="text-sm text-muted-foreground">
                                    This user account is currently suspended and cannot log in.
                                </p>
                                <Button
                                    variant="default"
                                    onClick={handleUnsuspend}
                                >
                                    <CheckCircle className="mr-2 h-4 w-4" />
                                    Unsuspend User
                                </Button>
                            </div>
                        ) : (
                            <div className="space-y-3">
                                <p className="text-sm text-muted-foreground">
                                    Suspending a user will prevent them from logging in to the application.
                                </p>
                                <Button
                                    variant="destructive"
                                    onClick={handleSuspend}
                                >
                                    <Ban className="mr-2 h-4 w-4" />
                                    Suspend User
                                </Button>
                            </div>
                        )}
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}

