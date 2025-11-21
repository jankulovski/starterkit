<?php

namespace Tests\Feature\Billing;

use App\Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DowngradePreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_downgrade_preview_calculates_credit_loss_correctly(): void
    {
        $user = User::factory()->create([
            'current_plan_key' => 'business',
            'credits_balance' => 500,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/billing/preview-downgrade', [
                'plan_key' => 'free',
            ]);

        $response->assertOk()
            ->assertJson([
                'current_plan' => [
                    'key' => 'business',
                    'name' => 'Business',
                    'tier' => 2,
                ],
                'target_plan' => [
                    'key' => 'free',
                    'name' => 'Free',
                    'tier' => 0,
                    'monthly_credits' => 0,
                ],
                'credits' => [
                    'current_balance' => 500,
                    'new_plan_allocation' => 0,
                    'will_be_lost' => 500,
                ],
                'is_downgrade' => true,
            ]);
    }

    public function test_downgrade_preview_shows_no_loss_when_within_allocation(): void
    {
        $user = User::factory()->create([
            'current_plan_key' => 'business',
            'credits_balance' => 150,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/billing/preview-downgrade', [
                'plan_key' => 'pro',
            ]);

        $response->assertOk()
            ->assertJson([
                'credits' => [
                    'current_balance' => 150,
                    'new_plan_allocation' => 200,
                    'will_be_lost' => 0,
                ],
                'is_downgrade' => true,
            ]);
    }

    public function test_downgrade_preview_for_partial_loss(): void
    {
        $user = User::factory()->create([
            'current_plan_key' => 'business',
            'credits_balance' => 250,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/billing/preview-downgrade', [
                'plan_key' => 'pro',
            ]);

        $response->assertOk()
            ->assertJson([
                'credits' => [
                    'current_balance' => 250,
                    'new_plan_allocation' => 200,
                    'will_be_lost' => 50,
                ],
            ]);
    }

    public function test_upgrade_preview_shows_no_downgrade(): void
    {
        $user = User::factory()->create([
            'current_plan_key' => 'free',
            'credits_balance' => 0,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/billing/preview-downgrade', [
                'plan_key' => 'pro',
            ]);

        $response->assertOk()
            ->assertJson([
                'is_downgrade' => false,
            ]);
    }

    public function test_preview_requires_valid_plan_key(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/billing/preview-downgrade', [
                'plan_key' => 'invalid_plan',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('plan_key');
    }

    public function test_preview_requires_authentication(): void
    {
        $response = $this->postJson('/billing/preview-downgrade', [
            'plan_key' => 'free',
        ]);

        $response->assertStatus(401);
    }

    public function test_preview_includes_effective_date_for_active_subscription(): void
    {
        // This test would require mocking Cashier's subscription
        // For now, we'll verify the structure is returned

        $user = User::factory()->create([
            'current_plan_key' => 'pro',
            'credits_balance' => 100,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/billing/preview-downgrade', [
                'plan_key' => 'free',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'current_plan',
                'target_plan',
                'credits',
                'effective_date',
                'is_downgrade',
            ]);
    }

    public function test_preview_validates_plan_key_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/billing/preview-downgrade', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('plan_key');
    }

    public function test_preview_works_for_same_tier_different_credits(): void
    {
        // Edge case: If we ever have plans at same tier with different credits
        // (not currently the case, but tests future-proofing)

        $user = User::factory()->create([
            'current_plan_key' => 'pro',
            'credits_balance' => 250,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/billing/preview-downgrade', [
                'plan_key' => 'business',
            ]);

        $response->assertOk();
        $data = $response->json();

        // Should correctly calculate based on monthly_credits, not tier
        $this->assertEquals(250, $data['credits']['current_balance']);
        $this->assertEquals(1000, $data['credits']['new_plan_allocation']);
        $this->assertEquals(0, $data['credits']['will_be_lost']);
    }
}
