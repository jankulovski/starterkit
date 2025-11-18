import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/input-error';
import { ArrowLeft, Shield, Mail, Calendar, CheckCircle2, XCircle, Ban, CheckCircle } from 'lucide-react';
import { Transition } from '@headlessui/react';

interface User {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    suspended_at: string | null;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
}

interface AdminUsersShowProps {
    user: User;
    success?: string;
    error?: string;
}

const breadcrumbs = (userId: number): BreadcrumbItem[] => [
    {
        title: 'Admin',
        href: '/admin',
    },
    {
        title: 'Users',
        href: '/admin/users',
    },
    {
        title: `User #${userId}`,
        href: `/admin/users/${userId}`,
    },
];

export default function AdminUsersShow({ user, success, error }: AdminUsersShowProps) {
    const { data, setData, patch, processing, errors, recentlySuccessful } = useForm({
        name: user.name,
        is_admin: user.is_admin,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        patch(`/admin/users/${user.id}`, {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs(user.id)}>
            <Head title={`User #${user.id} - Admin`} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">User Details</h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            View and manage user information
                        </p>
                    </div>
                    <Link
                        href="/admin/users"
                        className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Back to Users
                    </Link>
                </div>

                {success && (
                    <div className="rounded-lg bg-green-50 p-4 text-sm text-green-800 dark:bg-green-900/20 dark:text-green-400">
                        {success}
                    </div>
                )}

                {error && (
                    <div className="rounded-lg bg-red-50 p-4 text-sm text-red-800 dark:bg-red-900/20 dark:text-red-400">
                        {error}
                    </div>
                )}

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>User Information</CardTitle>
                            <CardDescription>
                                Basic account information
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center gap-3">
                                <Mail className="h-4 w-4 text-muted-foreground" />
                                <div>
                                    <p className="text-sm font-medium">Email</p>
                                    <p className="text-sm text-muted-foreground">
                                        {user.email}
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-center gap-3">
                                <Calendar className="h-4 w-4 text-muted-foreground" />
                                <div>
                                    <p className="text-sm font-medium">Created</p>
                                    <p className="text-sm text-muted-foreground">
                                        {new Date(user.created_at).toLocaleString()}
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-center gap-3">
                                {user.email_verified_at ? (
                                    <>
                                        <CheckCircle2 className="h-4 w-4 text-green-600" />
                                        <div>
                                            <p className="text-sm font-medium">Email Status</p>
                                            <p className="text-sm text-muted-foreground">
                                                Verified
                                            </p>
                                        </div>
                                    </>
                                ) : (
                                    <>
                                        <XCircle className="h-4 w-4 text-red-600" />
                                        <div>
                                            <p className="text-sm font-medium">Email Status</p>
                                            <p className="text-sm text-muted-foreground">
                                                Unverified
                                            </p>
                                        </div>
                                    </>
                                )}
                            </div>

                            <div className="flex items-center gap-3">
                                {user.two_factor_enabled ? (
                                    <>
                                        <CheckCircle2 className="h-4 w-4 text-green-600" />
                                        <div>
                                            <p className="text-sm font-medium">2FA Status</p>
                                            <p className="text-sm text-muted-foreground">
                                                Enabled
                                            </p>
                                        </div>
                                    </>
                                ) : (
                                    <>
                                        <XCircle className="h-4 w-4 text-muted-foreground" />
                                        <div>
                                            <p className="text-sm font-medium">2FA Status</p>
                                            <p className="text-sm text-muted-foreground">
                                                Disabled
                                            </p>
                                        </div>
                                    </>
                                )}
                            </div>

                            <div className="flex items-center gap-3">
                                <Shield className="h-4 w-4 text-muted-foreground" />
                                <div>
                                    <p className="text-sm font-medium">Role</p>
                                    {user.is_admin ? (
                                        <Badge variant="default" className="mt-1">
                                            Administrator
                                        </Badge>
                                    ) : (
                                        <Badge variant="secondary" className="mt-1">
                                            User
                                        </Badge>
                                    )}
                                </div>
                            </div>

                            <div className="flex items-center gap-3">
                                {user.suspended_at ? (
                                    <>
                                        <Ban className="h-4 w-4 text-red-600" />
                                        <div>
                                            <p className="text-sm font-medium">Account Status</p>
                                            <Badge variant="destructive" className="mt-1">
                                                Suspended
                                            </Badge>
                                            <p className="text-xs text-muted-foreground mt-1">
                                                Suspended on {new Date(user.suspended_at).toLocaleDateString()}
                                            </p>
                                        </div>
                                    </>
                                ) : (
                                    <>
                                        <CheckCircle className="h-4 w-4 text-green-600" />
                                        <div>
                                            <p className="text-sm font-medium">Account Status</p>
                                            <Badge variant="secondary" className="mt-1">
                                                Active
                                            </Badge>
                                        </div>
                                    </>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Edit User</CardTitle>
                            <CardDescription>
                                Update user information and permissions
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-6">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        required
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="flex items-center justify-between rounded-lg border p-4">
                                    <div className="space-y-0.5">
                                        <Label htmlFor="is_admin" className="text-base">
                                            Administrator
                                        </Label>
                                        <p className="text-sm text-muted-foreground">
                                            Grant admin privileges to this user
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Checkbox
                                            id="is_admin"
                                            checked={data.is_admin}
                                            onCheckedChange={(checked) => setData('is_admin', checked === true)}
                                        />
                                    </div>
                                </div>
                                <InputError message={errors.is_admin} />

                                <div className="flex items-center gap-4">
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
                                        <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                            Saved
                                        </p>
                                    </Transition>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Account Actions</CardTitle>
                        <CardDescription>
                            Suspend or unsuspend this user account
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {user.suspended_at ? (
                            <div className="space-y-4">
                                <p className="text-sm text-muted-foreground">
                                    This user account is currently suspended and cannot log in.
                                </p>
                                <Button
                                    variant="default"
                                    onClick={() => {
                                        router.post(`/admin/users/${user.id}/unsuspend`, {}, {
                                            preserveScroll: true,
                                        });
                                    }}
                                >
                                    <CheckCircle className="mr-2 h-4 w-4" />
                                    Unsuspend User
                                </Button>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                <p className="text-sm text-muted-foreground">
                                    Suspending a user will prevent them from logging in to the application.
                                </p>
                                <Button
                                    variant="destructive"
                                    onClick={() => {
                                        if (confirm('Are you sure you want to suspend this user? They will not be able to log in.')) {
                                            router.post(`/admin/users/${user.id}/suspend`, {}, {
                                                preserveScroll: true,
                                            });
                                        }
                                    }}
                                >
                                    <Ban className="mr-2 h-4 w-4" />
                                    Suspend User
                                </Button>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

