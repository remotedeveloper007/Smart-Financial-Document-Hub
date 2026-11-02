<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_plans_endpoint_and_subscribe(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);

        Plan::create(['slug' => 'starter', 'name' => 'Starter', 'price' => 0.00]);
        Plan::create(['slug' => 'pro', 'name' => 'Pro', 'price' => 49.00]);

        $this->actingAs($user);

        $response = $this->getJson(route('billing.plans'));
        $response->assertOk();
        $response->assertJsonCount(2, 'plans');

        $plan = Plan::where('slug', 'starter')->first();
        $response = $this->postJson(route('billing.subscriptions.store'), ['plan_id' => $plan->id]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('subscriptions', ['team_id' => $team->id, 'plan_id' => $plan->id]);
    }
}
