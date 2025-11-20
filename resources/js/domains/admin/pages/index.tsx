import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Users, Shield, UserPlus, ArrowRight } from 'lucide-react';

interface AdminIndexProps {
    metrics: {
        totalUsers: number;
        adminCount: number;
        recentSignups: Array<{
            id: number;
            name: string;
            email: string;
            created_at: string;
            is_admin: boolean;
        }>;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: '/admin',
    },
];

export default function AdminIndex({ metrics }: AdminIndexProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Admin Overview</h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Manage users and monitor system activity
                        </p>
                    </div>
                    <Link href="/admin/users">
                        <Button>
                            Manage Users
                            <ArrowRight className="ml-2 h-4 w-4" />
                        </Button>
                    </Link>
                </div>

                <div className="grid gap-6 md:grid-cols-3">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Total Users
                            </CardTitle>
                            <Users className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{metrics.totalUsers}</div>
                            <p className="text-xs text-muted-foreground">
                                All registered users
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Administrators
                            </CardTitle>
                            <Shield className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{metrics.adminCount}</div>
                            <p className="text-xs text-muted-foreground">
                                Users with admin privileges
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Recent Signups
                            </CardTitle>
                            <UserPlus className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{metrics.recentSignups.length}</div>
                            <p className="text-xs text-muted-foreground">
                                Latest 5 registrations
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {metrics.recentSignups.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Recent Signups</CardTitle>
                            <CardDescription>
                                Latest user registrations
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {metrics.recentSignups.map((user) => (
                                    <div
                                        key={user.id}
                                        className="flex items-center justify-between border-b border-border pb-4 last:border-0 last:pb-0"
                                    >
                                        <div>
                                            <p className="font-medium">{user.name}</p>
                                            <p className="text-sm text-muted-foreground">
                                                {user.email}
                                            </p>
                                        </div>
                                        <div className="text-right text-sm text-muted-foreground">
                                            {new Date(user.created_at).toLocaleDateString()}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}

