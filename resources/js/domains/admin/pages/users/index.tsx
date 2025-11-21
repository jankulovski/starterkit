import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { DataTable, type Column } from '@/components/data-table';
import { DataTableFilters, type FilterConfig } from '@/components/data-table-filters';
import { UserEditDialog } from '@/components/user-edit-dialog';
import { ArrowLeft } from 'lucide-react';
import { useState, useEffect, useCallback } from 'react';

interface User {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    suspended_at: string | null;
    created_at: string;
}

interface FullUser extends User {
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    updated_at: string;
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
        status?: string[];
    };
    filterCounts?: {
        admin: number;
        user: number;
        suspended: number;
    };
    user?: FullUser; // Only used for partial requests from dialog
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

export default function AdminUsersIndex({ 
    users, 
    filters: initialFilters,
    filterCounts = { admin: 0, user: 0, suspended: 0 }
}: AdminUsersIndexProps) {
    const [searchValue, setSearchValue] = useState(initialFilters.search || '');
    const [selectedStatus, setSelectedStatus] = useState<string[]>(initialFilters.status || []);
    const [selectedRows, setSelectedRows] = useState<Set<string | number>>(new Set());
    const [dialogOpen, setDialogOpen] = useState(false);
    const [selectedUserId, setSelectedUserId] = useState<number | null>(null);
    const [userData, setUserData] = useState<FullUser | null>(null);
    
    const { props } = usePage<AdminUsersIndexProps>();

    // Update userData when props.user changes (from Inertia response)
    useEffect(() => {
        if (props.user) {
            setUserData(props.user);
        }
    }, [props.user]);

    // Debounce search
    useEffect(() => {
        const timer = setTimeout(() => {
            applyFilters(searchValue, selectedStatus);
        }, 300);

        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [searchValue]);

    const applyFilters = useCallback((search: string, status: string[]) => {
        const params: Record<string, any> = {};
        
        if (search) {
            params.search = search;
        }
        
        if (status.length > 0) {
            params.status = status;
        }

        router.get('/admin/users', params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, []);

    const handleSearchChange = (value: string) => {
        setSearchValue(value);
    };

    const handleSearchSubmit = () => {
        applyFilters(searchValue, selectedStatus);
    };

    const handleFilterChange = (filterId: string, values: string[]) => {
        if (filterId === 'status') {
            setSelectedStatus(values);
            applyFilters(searchValue, values);
        }
    };

    const handleReset = () => {
        setSearchValue('');
        setSelectedStatus([]);
        router.get('/admin/users', {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
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

    const handleRowAction = (action: string, row: User) => {
        switch (action) {
            case 'view':
                setSelectedUserId(row.id);
                // Clear current user data if it's different to show loading state
                if (userData && userData.id !== row.id) {
                    setUserData(null);
                }
                // Visit the user show route but preserve the current URL
                router.visit(`/admin/users/${row.id}`, {
                    only: ['user'],
                    preserveState: true,
                    preserveScroll: true,
                    preserveUrl: true,  // Keep URL as /admin/users
                    onSuccess: () => {
                        setDialogOpen(true);
                    }
                });
                break;
            case 'suspend':
                router.post(`/admin/users/${row.id}/suspend`, {}, {
                    preserveScroll: true,
                    onSuccess: () => {
                        router.reload({ only: ['users'] });
                    },
                });
                break;
            case 'unsuspend':
                router.post(`/admin/users/${row.id}/unsuspend`, {}, {
                    preserveScroll: true,
                    onSuccess: () => {
                        router.reload({ only: ['users'] });
                    },
                });
                break;
        }
    };

    const handleDialogClose = (open: boolean) => {
        setDialogOpen(open);
        if (!open) {
            setSelectedUserId(null);
            setUserData(null);
        }
    };

    const handleDialogSuccess = () => {
        // Reload both users list and the selected user data
        if (selectedUserId) {
            router.reload({
                only: ['users', 'user'],
                preserveScroll: true,
            });
        } else {
            router.reload({
                only: ['users'],
                preserveScroll: true,
            });
        }
    };

    const getActionMenuItems = (user: User) => {
        const items = [
            {
                label: 'View',
                action: 'view',
            },
        ];

        if (user.suspended_at) {
            items.push({
                label: 'Unsuspend',
                action: 'unsuspend',
            });
        } else {
            items.push({
                label: 'Suspend',
                action: 'suspend',
                variant: 'destructive' as const,
            });
        }

        return items;
    };

    const columns: Column<User>[] = [
        {
            id: 'id',
            header: 'ID',
            accessorKey: 'id',
        },
        {
            id: 'name',
            header: 'Name',
            accessorKey: 'name',
            cell: (row) => (
                <span className="font-medium">{row.name}</span>
            ),
        },
        {
            id: 'email',
            header: 'Email',
            accessorKey: 'email',
            cell: (row) => (
                <span className="text-muted-foreground">{row.email}</span>
            ),
        },
        {
            id: 'created_at',
            header: 'Created',
            accessorKey: 'created_at',
            cell: (row) => (
                <span className="text-muted-foreground">
                    {new Date(row.created_at).toLocaleDateString()}
                </span>
            ),
        },
        {
            id: 'status',
            header: 'Status',
            cell: (row) => (
                <div className="flex flex-col gap-1">
                    {row.is_admin ? (
                        <Badge variant="default">Admin</Badge>
                    ) : (
                        <Badge variant="secondary">User</Badge>
                    )}
                    {row.suspended_at && (
                        <Badge variant="destructive" className="text-xs">
                            Suspended
                        </Badge>
                    )}
                </div>
            ),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Users - Admin" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6">
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

                <DataTableFilters
                    searchValue={searchValue}
                    onSearchChange={handleSearchChange}
                    onSearchSubmit={handleSearchSubmit}
                    filters={[
                        {
                            id: 'status',
                            label: 'Status',
                            options: [
                                { value: 'admin', label: 'Admin', count: filterCounts.admin },
                                { value: 'user', label: 'User', count: filterCounts.user },
                                { value: 'suspended', label: 'Suspended', count: filterCounts.suspended },
                            ],
                        },
                    ]}
                    selectedFilters={{
                        status: selectedStatus,
                    }}
                    onFilterChange={handleFilterChange}
                    onReset={handleReset}
                    searchPlaceholder="Filter users..."
                />

                <DataTable
                    data={users?.data || []}
                    columns={columns}
                    getRowId={(row) => row.id}
                    onRowAction={handleRowAction}
                    actionMenuItems={getActionMenuItems}
                    enableSelection={true}
                    selectedRows={selectedRows}
                    onSelectionChange={setSelectedRows}
                    emptyMessage={
                        searchValue || selectedStatus.length > 0
                            ? "No users found. Try adjusting your search criteria."
                            : "No users found"
                    }
                    pagination={users ? {
                        currentPage: users.current_page,
                        lastPage: users.last_page,
                        perPage: users.per_page,
                        total: users.total,
                        links: users.links,
                        onPageChange: handlePageChange,
                    } : undefined}
                />

                <UserEditDialog
                    open={dialogOpen}
                    onOpenChange={handleDialogClose}
                    user={userData}
                    onSuccess={handleDialogSuccess}
                />
            </div>
        </AppLayout>
    );
}
