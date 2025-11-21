<?php

namespace Tests\Feature\Billing;

use App\Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_their_invoices(): void
    {
        $user = User::factory()->create([
            'stripe_id' => 'cus_test123',
        ]);

        // Mock Stripe invoices response
        $mockInvoice = new \stdClass();
        $mockInvoice->id = 'in_test123';
        $mockInvoice->number = 'INV-2025-001';
        $mockInvoice->status = 'paid';
        $mockInvoice->amount_due = 2000;
        $mockInvoice->amount_paid = 2000;
        $mockInvoice->currency = 'usd';
        $mockInvoice->created = time();
        $mockInvoice->period_start = time();
        $mockInvoice->period_end = time();
        $mockInvoice->invoice_pdf = 'https://stripe.com/invoice.pdf';
        $mockInvoice->hosted_invoice_url = 'https://stripe.com/hosted';
        $mockInvoice->description = 'Pro Plan Subscription';

        $mockResponse = new \stdClass();
        $mockResponse->data = [$mockInvoice];

        $stripeMock = $this->mock(\Stripe\StripeClient::class);
        $invoicesMock = \Mockery::mock();
        $invoicesMock->shouldReceive('all')
            ->with([
                'customer' => $user->stripe_id,
                'limit' => 50,
            ])
            ->andReturn($mockResponse);
        $stripeMock->invoices = $invoicesMock;

        $response = $this->actingAs($user)
            ->getJson('/billing/invoices');

        $response->assertOk()
            ->assertJsonStructure([
                'invoices' => [
                    '*' => [
                        'id',
                        'number',
                        'status',
                        'amount_due',
                        'amount_paid',
                        'currency',
                        'created_at',
                        'period_start',
                        'period_end',
                        'pdf_url',
                        'hosted_url',
                        'description',
                    ],
                ],
            ]);

        $data = $response->json();
        $this->assertCount(1, $data['invoices']);
        $this->assertEquals('in_test123', $data['invoices'][0]['id']);
        $this->assertEquals('INV-2025-001', $data['invoices'][0]['number']);
        $this->assertEquals('USD', $data['invoices'][0]['currency']);
    }

    public function test_user_without_stripe_id_gets_empty_invoices(): void
    {
        $user = User::factory()->create([
            'stripe_id' => null,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/billing/invoices');

        $response->assertOk()
            ->assertJson([
                'invoices' => [],
            ]);
    }

    public function test_guest_cannot_view_invoices(): void
    {
        $response = $this->getJson('/billing/invoices');

        $response->assertStatus(401);
    }

    public function test_invoice_endpoint_handles_stripe_api_errors(): void
    {
        $user = User::factory()->create([
            'stripe_id' => 'cus_test456',
        ]);

        $stripeMock = $this->mock(\Stripe\StripeClient::class);
        $invoicesMock = \Mockery::mock();
        $invoicesMock->shouldReceive('all')
            ->andThrow(new \Stripe\Exception\ApiErrorException('API Error'));
        $stripeMock->invoices = $invoicesMock;

        $response = $this->actingAs($user)
            ->getJson('/billing/invoices');

        $response->assertStatus(500)
            ->assertJson([
                'invoices' => [],
                'error' => 'Failed to fetch invoices. Please try again later.',
            ]);
    }

    public function test_user_can_download_their_own_invoice_pdf(): void
    {
        $user = User::factory()->create([
            'stripe_id' => 'cus_test789',
        ]);

        $mockInvoice = new \stdClass();
        $mockInvoice->id = 'in_test789';
        $mockInvoice->customer = $user->stripe_id;
        $mockInvoice->invoice_pdf = 'https://stripe.com/invoice_789.pdf';

        $stripeMock = $this->mock(\Stripe\StripeClient::class);
        $invoicesMock = \Mockery::mock();
        $invoicesMock->shouldReceive('retrieve')
            ->with('in_test789')
            ->andReturn($mockInvoice);
        $stripeMock->invoices = $invoicesMock;

        $response = $this->actingAs($user)
            ->get('/billing/invoices/in_test789/pdf');

        $response->assertRedirect('https://stripe.com/invoice_789.pdf');
    }

    public function test_user_cannot_download_another_users_invoice(): void
    {
        $user = User::factory()->create([
            'stripe_id' => 'cus_user1',
        ]);

        $otherUser = User::factory()->create([
            'stripe_id' => 'cus_user2',
        ]);

        // Mock invoice that belongs to other user
        $mockInvoice = new \stdClass();
        $mockInvoice->id = 'in_other_user';
        $mockInvoice->customer = $otherUser->stripe_id;

        $stripeMock = $this->mock(\Stripe\StripeClient::class);
        $invoicesMock = \Mockery::mock();
        $invoicesMock->shouldReceive('retrieve')
            ->with('in_other_user')
            ->andReturn($mockInvoice);
        $stripeMock->invoices = $invoicesMock;

        $response = $this->actingAs($user)
            ->get('/billing/invoices/in_other_user/pdf');

        $response->assertStatus(403);
    }

    public function test_download_pdf_returns_404_for_nonexistent_invoice(): void
    {
        $user = User::factory()->create([
            'stripe_id' => 'cus_test999',
        ]);

        $stripeMock = $this->mock(\Stripe\StripeClient::class);
        $invoicesMock = \Mockery::mock();
        $invoicesMock->shouldReceive('retrieve')
            ->with('in_nonexistent')
            ->andThrow(new \Stripe\Exception\InvalidRequestException('Invoice not found', null));
        $stripeMock->invoices = $invoicesMock;

        $response = $this->actingAs($user)
            ->get('/billing/invoices/in_nonexistent/pdf');

        $response->assertStatus(404);
    }

    public function test_download_pdf_returns_404_if_no_pdf_available(): void
    {
        $user = User::factory()->create([
            'stripe_id' => 'cus_no_pdf',
        ]);

        $mockInvoice = new \stdClass();
        $mockInvoice->id = 'in_no_pdf';
        $mockInvoice->customer = $user->stripe_id;
        $mockInvoice->invoice_pdf = null;

        $stripeMock = $this->mock(\Stripe\StripeClient::class);
        $invoicesMock = \Mockery::mock();
        $invoicesMock->shouldReceive('retrieve')
            ->with('in_no_pdf')
            ->andReturn($mockInvoice);
        $stripeMock->invoices = $invoicesMock;

        $response = $this->actingAs($user)
            ->get('/billing/invoices/in_no_pdf/pdf');

        $response->assertStatus(404);
    }

    public function test_guest_cannot_download_invoice_pdf(): void
    {
        $response = $this->get('/billing/invoices/in_test/pdf');

        $response->assertStatus(401);
    }

    public function test_invoices_are_rate_limited(): void
    {
        $user = User::factory()->create([
            'stripe_id' => 'cus_rate_limit',
        ]);

        $mockResponse = new \stdClass();
        $mockResponse->data = [];

        $stripeMock = $this->mock(\Stripe\StripeClient::class);
        $invoicesMock = \Mockery::mock();
        $invoicesMock->shouldReceive('all')->andReturn($mockResponse);
        $stripeMock->invoices = $invoicesMock;

        // Make requests up to rate limit (20 per minute)
        for ($i = 0; $i < 20; $i++) {
            $response = $this->actingAs($user)->getJson('/billing/invoices');
            $response->assertOk();
        }

        // 21st request should be rate limited
        $response = $this->actingAs($user)->getJson('/billing/invoices');
        $response->assertStatus(429);
    }
}
