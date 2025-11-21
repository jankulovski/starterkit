import { useEffect, useState } from 'react';
import axios from 'axios';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { FileText, Download, ExternalLink, AlertCircle, Loader2 } from 'lucide-react';

interface Invoice {
    id: string;
    number: string | null;
    status: string;
    amount_due: number;
    amount_paid: number;
    currency: string;
    created_at: string;
    period_start: string | null;
    period_end: string | null;
    pdf_url: string | null;
    hosted_url: string | null;
    description: string | null;
}

export function InvoiceList() {
    const [invoices, setInvoices] = useState<Invoice[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        fetchInvoices();
    }, []);

    const fetchInvoices = async () => {
        setLoading(true);
        setError(null);

        try {
            const response = await axios.get<{ invoices: Invoice[] }>('/billing/invoices');
            setInvoices(response.data.invoices);
        } catch (err: any) {
            const errorMessage = err.response?.data?.error || 'Failed to load invoices';
            setError(errorMessage);
        } finally {
            setLoading(false);
        }
    };

    const formatAmount = (amount: number, currency: string) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency,
        }).format(amount / 100);
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    };

    const getStatusBadge = (status: string) => {
        const variants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
            paid: 'default',
            open: 'secondary',
            void: 'outline',
            uncollectible: 'destructive',
        };

        return (
            <Badge variant={variants[status] || 'secondary'} className="capitalize">
                {status}
            </Badge>
        );
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <FileText className="h-5 w-5" />
                    Invoice History
                </CardTitle>
                <CardDescription>View and download your past invoices</CardDescription>
            </CardHeader>
            <CardContent>
                {loading ? (
                    <div className="flex items-center justify-center py-12">
                        <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                    </div>
                ) : error ? (
                    <Alert variant="destructive">
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>{error}</AlertDescription>
                    </Alert>
                ) : invoices.length === 0 ? (
                    <div className="py-12 text-center text-muted-foreground">
                        <FileText className="mx-auto h-12 w-12 mb-4 opacity-50" />
                        <p>No invoices found</p>
                        <p className="text-sm mt-1">Invoices will appear here after your first payment</p>
                    </div>
                ) : (
                    <div className="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Invoice</TableHead>
                                    <TableHead>Date</TableHead>
                                    <TableHead>Period</TableHead>
                                    <TableHead>Amount</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {invoices.map((invoice) => (
                                    <TableRow key={invoice.id}>
                                        <TableCell className="font-medium">
                                            {invoice.number || invoice.id.slice(0, 12)}
                                        </TableCell>
                                        <TableCell>{formatDate(invoice.created_at)}</TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {invoice.period_start && invoice.period_end
                                                ? `${formatDate(invoice.period_start)} - ${formatDate(invoice.period_end)}`
                                                : '-'}
                                        </TableCell>
                                        <TableCell className="font-medium">
                                            {formatAmount(invoice.amount_paid, invoice.currency)}
                                        </TableCell>
                                        <TableCell>{getStatusBadge(invoice.status)}</TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                {invoice.pdf_url && (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <a
                                                            href={invoice.pdf_url}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="flex items-center gap-1"
                                                        >
                                                            <Download className="h-4 w-4" />
                                                            PDF
                                                        </a>
                                                    </Button>
                                                )}
                                                {invoice.hosted_url && (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <a
                                                            href={invoice.hosted_url}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="flex items-center gap-1"
                                                        >
                                                            <ExternalLink className="h-4 w-4" />
                                                            View
                                                        </a>
                                                    </Button>
                                                )}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
