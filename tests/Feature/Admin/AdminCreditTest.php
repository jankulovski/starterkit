<?php

namespace Tests\Feature\Admin;

use App\Domain\Billing\Models\CreditTransaction;
use App\Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AdminCreditTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_credits_to_user(): void
    {
        Log::spy();

        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['credits_balance' => 100]);

        $response = $this->actingAs($admin)
            ->postJson('/admin/credits/adjust', [
                'user_id' => $user->id,
                'amount' => 50,
                'reason' => 'Compensation for service outage on 2025-01-15',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'new_balance' => 150,
            ]);

        $this->assertEquals(150, $user->fresh()->credits_balance);

        // Should create transaction with admin metadata
        $transaction = CreditTransaction::where('user_id', $user->id)
            ->where('type', 'admin_grant')
            ->latest()
            ->first();

        $this->assertNotNull($transaction);
        $this->assertEquals(50, $transaction->amount);
        $this->assertEquals($admin->id, $transaction->metadata['adjusted_by']);
        $this->assertEquals($admin->email, $transaction->metadata['adjusted_by_email']);

        // Should log the action
        Log::shouldHaveReceived('info')
            ->with('Admin granted credits', \Mockery::on(function ($context) use ($admin, $user) {
                return $context['admin_id'] === $admin->id
                    && $context['user_id'] === $user->id
                    && $context['amount'] === 50;
            }));
    }

    public function test_admin_can_deduct_credits_from_user(): void
    {
        Log::spy();

        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['credits_balance' => 100]);

        $response = $this->actingAs($admin)
            ->postJson('/admin/credits/adjust', [
                'user_id' => $user->id,
                'amount' => -30,
                'reason' => 'Removing test credits accidentally granted',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'new_balance' => 70,
            ]);

        $this->assertEquals(70, $user->fresh()->credits_balance);

        // Should create deduction transaction
        $transaction = CreditTransaction::where('user_id', $user->id)
            ->where('type', 'admin_deduction')
            ->latest()
            ->first();

        $this->assertNotNull($transaction);
        $this->assertEquals(-30, $transaction->amount);

        // Should log warning for deduction
        Log::shouldHaveReceived('warning')
            ->with('Admin deducted credits', \Mockery::any());
    }

    public function test_admin_cannot_deduct_more_than_user_has(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['credits_balance' => 50]);

        $response = $this->actingAs($admin)
            ->postJson('/admin/credits/adjust', [
                'user_id' => $user->id,
                'amount' => -100,
                'reason' => 'Attempting to deduct too many credits',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);

        // Balance should remain unchanged
        $this->assertEquals(50, $user->fresh()->credits_balance);
    }

    public function test_non_admin_cannot_adjust_credits(): void
    {
        $regularUser = User::factory()->create(['is_admin' => false]);
        $targetUser = User::factory()->create(['credits_balance' => 100]);

        $response = $this->actingAs($regularUser)
            ->postJson('/admin/credits/adjust', [
                'user_id' => $targetUser->id,
                'amount' => 50,
                'reason' => 'Unauthorized attempt',
            ]);

        $response->assertStatus(403);

        // Balance should remain unchanged
        $this->assertEquals(100, $targetUser->fresh()->credits_balance);
    }

    public function test_guest_cannot_adjust_credits(): void
    {
        $user = User::factory()->create(['credits_balance' => 100]);

        $response = $this->postJson('/admin/credits/adjust', [
            'user_id' => $user->id,
            'amount' => 50,
            'reason' => 'Guest attempt',
        ]);

        $response->assertStatus(401);
    }

    public function test_credit_adjustment_requires_valid_user_id(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)
            ->postJson('/admin/credits/adjust', [
                'user_id' => 99999,
                'amount' => 50,
                'reason' => 'Testing invalid user',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('user_id');
    }

    public function test_credit_adjustment_requires_non_zero_amount(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['credits_balance' => 100]);

        $response = $this->actingAs($admin)
            ->postJson('/admin/credits/adjust', [
                'user_id' => $user->id,
                'amount' => 0,
                'reason' => 'Testing zero amount',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('amount');
    }

    public function test_credit_adjustment_requires_reason(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['credits_balance' => 100]);

        $response = $this->actingAs($admin)
            ->postJson('/admin/credits/adjust', [
                'user_id' => $user->id,
                'amount' => 50,
                'reason' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('reason');
    }

    public function test_credit_adjustment_reason_must_be_at_least_10_characters(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['credits_balance' => 100]);

        $response = $this->actingAs($admin)
            ->postJson('/admin/credits/adjust', [
                'user_id' => $user->id,
                'amount' => 50,
                'reason' => 'Short',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('reason');
    }

    public function test_admin_can_get_credit_history_for_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['credits_balance' => 100]);

        // Create some transactions
        $user->addCredits(50, 'subscription', 'Monthly allocation');
        $user->chargeCredits(20, 'usage', 'API usage');
        $user->addCredits(10, 'admin_grant', 'Bonus credits', [
            'adjusted_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->getJson("/admin/credits/history?user_id={$user->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'current_balance',
                ],
                'transactions' => [
                    '*' => [
                        'id',
                        'amount',
                        'type',
                        'description',
                        'balance_after',
                        'metadata',
                        'created_at',
                    ],
                ],
            ]);

        $data = $response->json();
        $this->assertEquals($user->id, $data['user']['id']);
        $this->assertEquals(3, count($data['transactions']));
    }

    public function test_credit_history_limits_results(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['credits_balance' => 100]);

        // Create many transactions
        for ($i = 0; $i < 60; $i++) {
            CreditTransaction::create([
                'user_id' => $user->id,
                'amount' => 10,
                'type' => 'subscription',
                'description' => "Transaction {$i}",
                'balance_after' => 100 + ($i * 10),
            ]);
        }

        // Request with limit
        $response = $this->actingAs($admin)
            ->getJson("/admin/credits/history?user_id={$user->id}&limit=20");

        $response->assertOk();
        $data = $response->json();

        $this->assertEquals(20, count($data['transactions']));
    }

    public function test_non_admin_cannot_view_credit_history(): void
    {
        $regularUser = User::factory()->create(['is_admin' => false]);
        $targetUser = User::factory()->create();

        $response = $this->actingAs($regularUser)
            ->getJson("/admin/credits/history?user_id={$targetUser->id}");

        $response->assertStatus(403);
    }
}
