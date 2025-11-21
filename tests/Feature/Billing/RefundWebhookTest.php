<?php

namespace Tests\Feature\Billing;

use App\Domain\Billing\Models\CreditTransaction;
use App\Domain\Billing\Models\ProcessedWebhookEvent;
use App\Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class RefundWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Stripe client to avoid real API calls
        $this->mock(\Stripe\StripeClient::class);
    }

    public function test_refund_webhook_reverses_credits_for_credit_pack(): void
    {
        Log::spy();

        $user = User::factory()->create([
            'stripe_id' => 'cus_test123',
            'credits_balance' => 0,
        ]);

        // Simulate credit pack purchase with charge_id
        $chargeId = 'ch_test_refund_123';
        $user->addCredits(100, 'purchase', 'Test purchase', [
            'charge_id' => $chargeId,
            'session_id' => 'cs_test_123',
        ]);

        $this->assertEquals(100, $user->fresh()->credits_balance);

        // Create refund webhook payload
        $payload = [
            'id' => 'evt_test_refund',
            'type' => 'charge.refunded',
            'data' => [
                'object' => [
                    'id' => $chargeId,
                    'customer' => $user->stripe_id,
                    'amount' => 10000,
                    'currency' => 'usd',
                    'refunded' => true,
                ],
            ],
        ];

        // Process webhook
        $response = $this->postJson('/stripe/webhook', $payload);

        $response->assertOk();

        // Credits should be reversed
        $this->assertEquals(0, $user->fresh()->credits_balance);

        // Should create a refund transaction
        $refundTransaction = CreditTransaction::where('user_id', $user->id)
            ->where('type', 'refund')
            ->first();

        $this->assertNotNull($refundTransaction);
        $this->assertEquals(-100, $refundTransaction->amount);
        $this->assertEquals(0, $refundTransaction->balance_after);

        // Event should be marked as processed
        $this->assertTrue(ProcessedWebhookEvent::isProcessed('evt_test_refund'));

        // Should log the reversal
        Log::shouldHaveReceived('warning')
            ->with('Credits reversed due to refund', \Mockery::on(function ($context) use ($chargeId) {
                return $context['charge_id'] === $chargeId
                    && $context['credits_reversed'] === 100;
            }));
    }

    public function test_refund_webhook_logs_error_if_insufficient_balance(): void
    {
        Log::spy();

        $user = User::factory()->create([
            'stripe_id' => 'cus_test456',
            'credits_balance' => 50,
        ]);

        $chargeId = 'ch_test_insufficient';

        // Create original purchase transaction
        CreditTransaction::create([
            'user_id' => $user->id,
            'amount' => 100,
            'type' => 'purchase',
            'description' => 'Original purchase',
            'balance_after' => 100,
            'metadata' => ['charge_id' => $chargeId],
        ]);

        // Refund webhook
        $payload = [
            'id' => 'evt_insufficient',
            'type' => 'charge.refunded',
            'data' => [
                'object' => [
                    'id' => $chargeId,
                    'customer' => $user->stripe_id,
                ],
            ],
        ];

        $response = $this->postJson('/stripe/webhook', $payload);

        $response->assertOk();

        // Balance should remain unchanged
        $this->assertEquals(50, $user->fresh()->credits_balance);

        // Should log error for manual review
        Log::shouldHaveReceived('error')
            ->with('Cannot reverse credits: insufficient balance', \Mockery::on(function ($context) {
                return $context['requires_manual_review'] === true
                    && $context['credits_to_reverse'] === 100
                    && $context['current_balance'] === 50;
            }));
    }

    public function test_refund_webhook_ignores_subscription_refunds(): void
    {
        Log::spy();

        $user = User::factory()->create([
            'stripe_id' => 'cus_test789',
            'credits_balance' => 200,
        ]);

        // Refund for a charge that's NOT a credit pack
        $payload = [
            'id' => 'evt_subscription_refund',
            'type' => 'charge.refunded',
            'data' => [
                'object' => [
                    'id' => 'ch_subscription_123',
                    'customer' => $user->stripe_id,
                ],
            ],
        ];

        $response = $this->postJson('/stripe/webhook', $payload);

        $response->assertOk();

        // Balance should remain unchanged
        $this->assertEquals(200, $user->fresh()->credits_balance);

        // Should log that no transaction was found
        Log::shouldHaveReceived('info')
            ->with('Refund received but no credit pack transaction found (likely subscription refund)', \Mockery::any());
    }

    public function test_refund_webhook_is_idempotent(): void
    {
        $user = User::factory()->create([
            'stripe_id' => 'cus_idempotent',
            'credits_balance' => 100,
        ]);

        $chargeId = 'ch_idempotent';
        $user->addCredits(50, 'purchase', 'Test', ['charge_id' => $chargeId]);

        $payload = [
            'id' => 'evt_idempotent',
            'type' => 'charge.refunded',
            'data' => [
                'object' => [
                    'id' => $chargeId,
                    'customer' => $user->stripe_id,
                ],
            ],
        ];

        // First webhook
        $this->postJson('/stripe/webhook', $payload)->assertOk();
        $this->assertEquals(100, $user->fresh()->credits_balance);

        // Second webhook (duplicate)
        $this->postJson('/stripe/webhook', $payload)->assertOk();
        $this->assertEquals(100, $user->fresh()->credits_balance);

        // Should only have one refund transaction
        $refundCount = CreditTransaction::where('user_id', $user->id)
            ->where('type', 'refund')
            ->count();

        $this->assertEquals(1, $refundCount);
    }

    public function test_dispute_webhook_logs_alert_without_reversing_credits(): void
    {
        Log::spy();

        $user = User::factory()->create([
            'stripe_id' => 'cus_dispute',
            'credits_balance' => 100,
        ]);

        $chargeId = 'ch_disputed';
        $user->addCredits(50, 'purchase', 'Test', ['charge_id' => $chargeId]);

        // Mock Stripe charge retrieval
        $mockCharge = new \stdClass();
        $mockCharge->customer = $user->stripe_id;

        $stripeMock = $this->mock(\Stripe\StripeClient::class);
        $chargesMock = \Mockery::mock();
        $chargesMock->shouldReceive('retrieve')
            ->with($chargeId)
            ->andReturn($mockCharge);
        $stripeMock->charges = $chargesMock;

        $payload = [
            'id' => 'evt_dispute',
            'type' => 'charge.dispute.created',
            'data' => [
                'object' => [
                    'id' => 'dp_test123',
                    'charge' => $chargeId,
                    'amount' => 5000,
                    'reason' => 'fraudulent',
                ],
            ],
        ];

        $response = $this->postJson('/stripe/webhook', $payload);

        $response->assertOk();

        // Credits should NOT be reversed yet
        $this->assertEquals(150, $user->fresh()->credits_balance);

        // Should log critical alert
        Log::shouldHaveReceived('error')
            ->with('CHARGEBACK ALERT: Dispute filed against charge', \Mockery::on(function ($context) {
                return $context['requires_investigation'] === true
                    && $context['is_credit_pack_purchase'] === true;
            }));
    }
}
