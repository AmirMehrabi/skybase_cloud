<?php

namespace Tests\Feature;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_page_displays_plans(): void
    {
        Plan::factory()->create([
            'name' => 'Test Plan',
            'internal_name' => 'test_plan',
            'status' => 'active',
            'type' => 'pppoe',
            'price' => 29.99,
        ]);

        $response = $this->get(route('plans.index'));

        $response->assertStatus(200);
        $response->assertViewHas('plans');
        $response->assertSee('Test Plan');
    }

    public function test_create_page_displays_form(): void
    {
        $response = $this->get(route('plans.create'));

        $response->assertStatus(200);
        $response->assertSee('Create Plan');
    }

    public function test_can_create_new_plan(): void
    {
        $planData = [
            'name' => 'New Plan',
            'internal_name' => 'new_plan',
            'description' => 'Test description',
            'status' => 'active',
            'visibility' => 'public',
            'type' => 'pppoe',
            'category' => 'Residential',
            'download_speed' => 100,
            'upload_speed' => 50,
            'bandwidth_unit' => 'Mbps',
            'data_limit' => 500,
            'data_unit' => 'GB',
            'unlimited' => false,
            'price' => 49.99,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'setup_fee' => 0,
            'priority' => 5,
        ];

        $response = $this->post(route('plans.store'), $planData);

        $response->assertRedirect(route('plans.show', Plan::where('internal_name', 'new_plan')->first()));
        $this->assertDatabaseHas('plans', [
            'internal_name' => 'new_plan',
            'name' => 'New Plan',
        ]);
    }

    public function test_can_update_existing_plan(): void
    {
        $plan = Plan::factory()->create([
            'name' => 'Original Name',
            'internal_name' => 'original_plan',
            'status' => 'active',
            'type' => 'pppoe',
            'price' => 29.99,
        ]);

        $updatedData = [
            'name' => 'Updated Name',
            'internal_name' => 'original_plan',
            'description' => 'Updated description',
            'status' => 'active',
            'visibility' => 'public',
            'type' => 'fiber',
            'category' => 'Business',
            'download_speed' => 1000,
            'upload_speed' => 1000,
            'bandwidth_unit' => 'Mbps',
            'data_limit' => null,
            'data_unit' => 'GB',
            'unlimited' => true,
            'price' => 199.99,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'setup_fee' => 50,
            'priority' => 8,
        ];

        $response = $this->put(route('plans.update', $plan), $updatedData);

        $response->assertRedirect(route('plans.show', $plan));
        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_can_delete_plan(): void
    {
        $plan = Plan::factory()->create([
            'name' => 'To Delete',
            'internal_name' => 'to_delete',
            'status' => 'active',
            'type' => 'pppoe',
            'price' => 19.99,
        ]);

        $response = $this->delete(route('plans.destroy', $plan));

        $response->assertRedirect(route('plans.index'));
        $this->assertDatabaseMissing('plans', [
            'id' => $plan->id,
        ]);
    }

    public function test_show_page_displays_plan_details(): void
    {
        $plan = Plan::factory()->create([
            'name' => 'Detail Test',
            'internal_name' => 'detail_test',
            'status' => 'active',
            'type' => 'pppoe',
            'price' => 39.99,
        ]);

        $response = $this->get(route('plans.show', $plan));

        $response->assertStatus(200);
        $response->assertSee('Detail Test');
    }

    public function test_edit_page_displays_form_with_plan_data(): void
    {
        $plan = Plan::factory()->create([
            'name' => 'Edit Test',
            'internal_name' => 'edit_test',
            'status' => 'active',
            'type' => 'pppoe',
            'price' => 34.99,
        ]);

        $response = $this->get(route('plans.edit', $plan));

        $response->assertStatus(200);
        $response->assertSee('Edit Plan');
        $response->assertSee('Edit Test');
    }
}
