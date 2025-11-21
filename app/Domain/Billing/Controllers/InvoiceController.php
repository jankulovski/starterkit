<?php

namespace App\Domain\Billing\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class InvoiceController extends Controller
{
    public function __construct(
        protected StripeClient $stripe
    ) {
    }

    /**
     * Get invoice history for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // User must have a Stripe customer ID to have invoices
        if (!$user->stripe_id) {
            return response()->json([
                'invoices' => [],
            ]);
        }

        try {
            // Fetch invoices from Stripe
            $stripeInvoices = $this->stripe->invoices->all([
                'customer' => $user->stripe_id,
                'limit' => 50,
            ]);

            $invoices = collect($stripeInvoices->data)->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'status' => $invoice->status,
                    'amount_due' => $invoice->amount_due,
                    'amount_paid' => $invoice->amount_paid,
                    'currency' => strtoupper($invoice->currency),
                    'created_at' => date('Y-m-d H:i:s', $invoice->created),
                    'period_start' => $invoice->period_start ? date('Y-m-d', $invoice->period_start) : null,
                    'period_end' => $invoice->period_end ? date('Y-m-d', $invoice->period_end) : null,
                    'pdf_url' => $invoice->invoice_pdf,
                    'hosted_url' => $invoice->hosted_invoice_url,
                    'description' => $invoice->description,
                ];
            })->toArray();

            return response()->json([
                'invoices' => $invoices,
            ]);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Failed to fetch invoices from Stripe', [
                'user_id' => $user->id,
                'stripe_id' => $user->stripe_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'invoices' => [],
                'error' => 'Failed to fetch invoices. Please try again later.',
            ], 500);
        }
    }

    /**
     * Download a specific invoice PDF.
     */
    public function downloadPdf(Request $request, string $invoiceId)
    {
        $user = $request->user();

        if (!$user->stripe_id) {
            abort(404, 'No invoices found');
        }

        try {
            // Fetch the invoice to verify it belongs to this user
            $invoice = $this->stripe->invoices->retrieve($invoiceId);

            // Security: Verify invoice belongs to the authenticated user
            if ($invoice->customer !== $user->stripe_id) {
                Log::warning('User attempted to access another user\'s invoice', [
                    'user_id' => $user->id,
                    'user_stripe_id' => $user->stripe_id,
                    'invoice_id' => $invoiceId,
                    'invoice_customer' => $invoice->customer,
                ]);

                abort(403, 'Unauthorized');
            }

            // Redirect to Stripe's hosted PDF
            if ($invoice->invoice_pdf) {
                return redirect($invoice->invoice_pdf);
            }

            abort(404, 'Invoice PDF not available');
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            abort(404, 'Invoice not found');
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Failed to retrieve invoice', [
                'user_id' => $user->id,
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);

            abort(500, 'Failed to retrieve invoice');
        }
    }
}
