import { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import axios from 'axios';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    DollarSign,
    Users,
    CreditCard,
    TrendingUp,
    TrendingDown,
    Activity,
    AlertTriangle,
    CheckCircle2,
    Loader2,
    RefreshCcw,
    ArrowLeft,
} from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: '/admin',
    },
    {
        title: 'Billing Metrics',
        href: '/admin/billing/metrics',
    },
];

interface MetricsData {
    revenue: {
        mrr: number;
        arr: number;
        growth_rate: number;
    };
    subscriptions: {
        total_active: number;
        by_plan: Record<string, number>;
        new_this_period: number;
        canceled_this_period: number;
        conversion_rate: number;
    };
    credits: {
        total_allocated: number;
        total_charged: number;
        net_change: number;
        purchased: number;
        by_type: Record<string, number>;
        burn_rate: number;
    };
    users: {
        total: number;
        active_subscribers: number;
        free_users: number;
        suspended: number;
        activation_rate: number;
    };
    churn: {
        churned_users: number;
        churn_rate: number;
        retention_rate: number;
    };
}

interface HealthData {
    status: string;
    checks: Record<
        string,
        {
            status: string;
            [key: string]: any;
        }
    >;
}

export default function BillingMetrics({
    metrics: initialMetrics,
    health: initialHealth,
    period: initialPeriod,
}: {
    metrics: MetricsData;
    health: HealthData;
    period: string;
}) {
    const [metrics, setMetrics] = useState<MetricsData>(initialMetrics);
    const [health, setHealth] = useState<HealthData>(initialHealth);
    const [period, setPeriod] = useState(initialPeriod);
    const [loading, setLoading] = useState(false);
    const [autoRefresh, setAutoRefresh] = useState(false);

    useEffect(() => {
        if (autoRefresh) {
            const interval = setInterval(() => {
                fetchMetrics();
            }, 30000); // Refresh every 30 seconds

            return () => clearInterval(interval);
        }
    }, [autoRefresh, period]);

    const fetchMetrics = async () => {
        setLoading(true);
        try {
            const response = await axios.get('/admin/billing/metrics/realtime', {
                params: { period },
            });
            setMetrics(response.data.metrics);
            setHealth(response.data.health);
        } catch (error) {
            console.error('Failed to fetch metrics:', error);
        } finally {
            setLoading(false);
        }
    };

    const handlePeriodChange = async (newPeriod: string) => {
        setPeriod(newPeriod);
        setLoading(true);
        try {
            const response = await axios.get('/admin/billing/metrics/realtime', {
                params: { period: newPeriod },
            });
            setMetrics(response.data.metrics);
            setHealth(response.data.health);
        } catch (error) {
            console.error('Failed to fetch metrics:', error);
        } finally {
            setLoading(false);
        }
    };

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
        }).format(amount);
    };

    const getHealthBadge = (status: string) => {
        const variants = {
            healthy: { variant: 'default' as const, icon: CheckCircle2, text: 'Healthy' },
            warning: { variant: 'secondary' as const, icon: AlertTriangle, text: 'Warning' },
            critical: { variant: 'destructive' as const, icon: AlertTriangle, text: 'Critical' },
        };

        const config = variants[status as keyof typeof variants] || variants.healthy;
        const Icon = config.icon;

        return (
            <Badge variant={config.variant} className="flex items-center gap-1">
                <Icon className="h-3 w-3" />
                {config.text}
            </Badge>
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Billing Metrics - Admin" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Billing Metrics</h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Monitor your billing system performance
                        </p>
                    </div>
                    <div className="flex items-center gap-3">
                        <Select value={period} onValueChange={handlePeriodChange}>
                            <SelectTrigger className="w-[180px]">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="today">Today</SelectItem>
                                <SelectItem value="week">This Week</SelectItem>
                                <SelectItem value="month">This Month</SelectItem>
                            </SelectContent>
                        </Select>
                        <Button
                            variant="outline"
                            size="icon"
                            onClick={fetchMetrics}
                            disabled={loading}
                        >
                            {loading ? (
                                <Loader2 className="h-4 w-4 animate-spin" />
                            ) : (
                                <RefreshCcw className="h-4 w-4" />
                            )}
                        </Button>
                        <Button
                            variant={autoRefresh ? 'default' : 'outline'}
                            onClick={() => setAutoRefresh(!autoRefresh)}
                        >
                            Auto Refresh {autoRefresh ? 'On' : 'Off'}
                        </Button>
                        <Link
                            href="/admin"
                            className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Back to Admin
                        </Link>
                    </div>
                </div>

                {/* Health Status */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Activity className="h-5 w-5" />
                            System Health
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-4">
                            {Object.entries(health.checks).map(([component, check]) => (
                                <div key={component} className="flex items-center justify-between">
                                    <span className="capitalize">{component}</span>
                                    {getHealthBadge(check.status)}
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                {/* Key Metrics Grid */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {/* MRR */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Monthly Recurring Revenue</CardTitle>
                            <DollarSign className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(metrics.revenue.mrr)}</div>
                            <p className="text-xs text-muted-foreground">
                                ARR: {formatCurrency(metrics.revenue.arr)}
                            </p>
                        </CardContent>
                    </Card>

                    {/* Active Subscriptions */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Active Subscriptions</CardTitle>
                            <CreditCard className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{metrics.subscriptions.total_active}</div>
                            <p className="text-xs text-muted-foreground">
                                Conversion: {metrics.subscriptions.conversion_rate}%
                            </p>
                        </CardContent>
                    </Card>

                    {/* Total Users */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Users</CardTitle>
                            <Users className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{metrics.users.total.toLocaleString()}</div>
                            <p className="text-xs text-muted-foreground">
                                {metrics.users.active_subscribers} paying
                            </p>
                        </CardContent>
                    </Card>

                    {/* Churn Rate */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Retention Rate</CardTitle>
                            {metrics.churn.retention_rate >= 90 ? (
                                <TrendingUp className="h-4 w-4 text-green-500" />
                            ) : (
                                <TrendingDown className="h-4 w-4 text-red-500" />
                            )}
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{metrics.churn.retention_rate}%</div>
                            <p className="text-xs text-muted-foreground">
                                Churn: {metrics.churn.churn_rate}%
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Detailed Metrics */}
                <div className="grid gap-4 md:grid-cols-2">
                    {/* Subscription Breakdown */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Subscription Breakdown</CardTitle>
                            <CardDescription>Active subscriptions by plan</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                {Object.entries(metrics.subscriptions.by_plan).map(([plan, count]) => (
                                    <div key={plan} className="flex items-center justify-between">
                                        <span className="capitalize text-sm">{plan}</span>
                                        <Badge variant="outline">{count}</Badge>
                                    </div>
                                ))}
                            </div>
                            <div className="mt-4 pt-4 border-t space-y-1">
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">New this period</span>
                                    <span className="font-medium text-green-600">
                                        +{metrics.subscriptions.new_this_period}
                                    </span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Canceled this period</span>
                                    <span className="font-medium text-red-600">
                                        -{metrics.subscriptions.canceled_this_period}
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Credit Metrics */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Credit Metrics</CardTitle>
                            <CardDescription>Credit allocation and usage</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <span className="text-sm">Allocated</span>
                                    <span className="font-medium text-green-600">
                                        +{metrics.credits.total_allocated.toLocaleString()}
                                    </span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span className="text-sm">Charged</span>
                                    <span className="font-medium text-red-600">
                                        -{metrics.credits.total_charged.toLocaleString()}
                                    </span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span className="text-sm">Purchased</span>
                                    <span className="font-medium">{metrics.credits.purchased.toLocaleString()}</span>
                                </div>
                                <div className="flex items-center justify-between pt-2 border-t">
                                    <span className="text-sm font-medium">Net Change</span>
                                    <span className={`font-bold ${metrics.credits.net_change >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                                        {metrics.credits.net_change >= 0 ? '+' : ''}
                                        {metrics.credits.net_change.toLocaleString()}
                                    </span>
                                </div>
                            </div>
                            <div className="mt-4 pt-4 border-t">
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Daily Burn Rate</span>
                                    <span className="font-medium">
                                        {metrics.credits.burn_rate.toLocaleString()} credits/day
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
