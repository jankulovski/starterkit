import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Search, Users as UsersIcon, ArrowLeft } from 'lucide-react';

interface User {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    suspended_at: string | null;
    created_at: string;
}

interface PaginatedUsers {
    data: User[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

interface AdminUsersIndexProps {
    users: PaginatedUsers;
    filters: {
        search?: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: '/admin',
    },
    {
        title: 'Users',
        href: '/admin/users',
    },
];

export default function AdminUsersIndex({ users, filters: initialFilters }: AdminUsersIndexProps) {
    const { data, setData, get, processing } = useForm({
        search: initialFilters.search || '',
    });

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        get('/admin/users', {
            preserveState: true,
            preserveScroll: true,
            data: {
                search: data.search,
            },
        });
    };

    const handlePageChange = (url: string | null) => {
        if (url) {
            router.visit(url, {
                preserveState: true,
                preserveScroll: true,
            });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Users - Admin" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">User Management</h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            View and manage all users
                        </p>
                    </div>
                    <Link
                        href="/admin"
                        className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Back to Admin
                    </Link>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Search Users</CardTitle>
                        <CardDescription>
                            Search by name or email address
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSearch} className="flex gap-2">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    type="text"
                                    placeholder="Search by name or email..."
                                    value={data.search}
                                    onChange={(e) => setData('search', e.target.value)}
                                    className="pl-9"
                                />
                            </div>
                            <Button type="submit" disabled={processing}>
                                Search
                            </Button>
                            {data.search && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => {
                                        setData('search', '');
                                        router.get('/admin/users');
                                    }}
                                >
                                    Clear
                                </Button>
                            )}
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle>Users</CardTitle>
                                <CardDescription>
                                    Showing {users.data.length} of {users.total} users
                                </CardDescription>
                            </div>
                            <UsersIcon className="h-5 w-5 text-muted-foreground" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        {users.data.length === 0 ? (
                            <div className="py-8 text-center text-muted-foreground">
                                <UsersIcon className="mx-auto h-12 w-12 mb-4 opacity-50" />
                                <p>No users found</p>
                                {data.search && (
                                    <p className="mt-2 text-sm">
                                        Try adjusting your search criteria
                                    </p>
                                )}
                            </div>
                        ) : (
                            <>
                                <div className="overflow-x-auto">
                                    <table className="w-full">
                                        <thead>
                                            <tr className="border-b">
                                                <th className="px-4 py-3 text-left text-sm font-medium">
                                                    ID
                                                </th>
                                                <th className="px-4 py-3 text-left text-sm font-medium">
                                                    Name
                                                </th>
                                                <th className="px-4 py-3 text-left text-sm font-medium">
                                                    Email
                                                </th>
                                                <th className="px-4 py-3 text-left text-sm font-medium">
                                                    Created
                                                </th>
                                                <th className="px-4 py-3 text-left text-sm font-medium">
                                                    Status
                                                </th>
                                                <th className="px-4 py-3 text-right text-sm font-medium">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {users.data.map((user) => (
                                                <tr
                                                    key={user.id}
                                                    className="border-b hover:bg-muted/50 transition-colors"
                                                >
                                                    <td className="px-4 py-3 text-sm">
                                                        {user.id}
                                                    </td>
                                                    <td className="px-4 py-3 text-sm font-medium">
                                                        {user.name}
                                                    </td>
                                                    <td className="px-4 py-3 text-sm text-muted-foreground">
                                                        {user.email}
                                                    </td>
                                                    <td className="px-4 py-3 text-sm text-muted-foreground">
                                                        {new Date(user.created_at).toLocaleDateString()}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <div className="flex flex-col gap-1">
                                                            {user.is_admin ? (
                                                                <Badge variant="default">Admin</Badge>
                                                            ) : (
                                                                <Badge variant="secondary">User</Badge>
                                                            )}
                                                            {user.suspended_at && (
                                                                <Badge variant="destructive" className="text-xs">
                                                                    Suspended
                                                                </Badge>
                                                            )}
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-3 text-right">
                                                        <Link
                                                            href={`/admin/users/${user.id}`}
                                                            className="text-sm text-primary hover:underline"
                                                        >
                                                            View
                                                        </Link>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                {users.last_page > 1 && (
                                    <div className="mt-4 flex items-center justify-between border-t pt-4">
                                        <div className="text-sm text-muted-foreground">
                                            Page {users.current_page} of {users.last_page}
                                        </div>
                                        <div className="flex gap-2">
                                            {users.links.map((link, index) => {
                                                if (index === 0) {
                                                    return (
                                                        <Button
                                                            key={index}
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => handlePageChange(link.url)}
                                                            disabled={!link.url || link.active}
                                                        >
                                                            Previous
                                                        </Button>
                                                    );
                                                }
                                                if (index === users.links.length - 1) {
                                                    return (
                                                        <Button
                                                            key={index}
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => handlePageChange(link.url)}
                                                            disabled={!link.url || link.active}
                                                        >
                                                            Next
                                                        </Button>
                                                    );
                                                }
                                                if (
                                                    link.label === '...' ||
                                                    Math.abs(
                                                        parseInt(link.label) - users.current_page
                                                    ) > 2
                                                ) {
                                                    return null;
                                                }
                                                return (
                                                    <Button
                                                        key={index}
                                                        variant={link.active ? 'default' : 'outline'}
                                                        size="sm"
                                                        onClick={() => handlePageChange(link.url)}
                                                        disabled={!link.url}
                                                    >
                                                        {link.label}
                                                    </Button>
                                                );
                                            })}
                                        </div>
                                    </div>
                                )}
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

