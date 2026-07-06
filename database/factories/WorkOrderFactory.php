<?php

namespace Database\Factories;

use App\Enums\WorkOrderPriority;
use App\Enums\WorkOrderStatus;
use App\Enums\WorkOrderType;
use App\Models\Customer;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WorkOrder> */
class WorkOrderFactory extends Factory
{
    protected $model = WorkOrder::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $customer = Customer::query()->inRandomOrder()->first();

        return [
            'tenant_id' => $customer?->tenant_id,
            'work_order_number' => 'WO-'.now()->format('ymd').'-'.fake()->unique()->numerify('####'),
            'customer_id' => $customer?->id,
            'created_by_user_id' => User::query()->where('tenant_id', $customer?->tenant_id)->value('id'),
            'type' => WorkOrderType::Other,
            'source' => 'manual',
            'priority' => WorkOrderPriority::Normal,
            'status' => WorkOrderStatus::Draft,
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'service_address_line1' => fake()->streetAddress(),
            'service_city' => fake()->city(),
            'contact_name' => fake()->name(),
            'contact_phone' => fake()->phoneNumber(),
        ];
    }
}
