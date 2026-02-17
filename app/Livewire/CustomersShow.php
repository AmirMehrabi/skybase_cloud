<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class CustomersShow extends Component
{
    public int $customerId;

    public string $activeTab = 'overview';

    public array $tabs = [
        'overview' => 'Overview',
        'services' => 'Services',
        'invoices' => 'Invoices',
        'usage' => 'Usage',
        'tickets' => 'Tickets',
        'activity' => 'Activity Log',
    ];

    public array $customer = [];

    public function mount(int $id): void
    {
        $this->customerId = $id;
        $this->loadCustomer();
    }

    private function loadCustomer(): void
    {
        $this->customer = $this->getDummyCustomer($this->customerId);
    }

    private function getDummyCustomer(int $id): array
    {
        return [
            'id' => $id,
            'customer_code' => 'CUS-2024-0001',
            'type' => 'individual',
            'first_name' => 'John',
            'last_name' => 'Anderson',
            'company_name' => null,
            'email' => 'john.anderson@email.com',
            'phone' => '+1 (555) 123-4567',
            'mobile' => '+1 (555) 987-6543',
            'whatsapp' => '+1 (555) 987-6543',
            'address' => '123 Main Street, Apt 4B',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'United States',
            'national_id' => '123-45-6789',
            'plan' => 'Fiber 100 Mbps',
            'site' => 'Downtown Hub',
            'router' => 'Mikrotik-01',
            'ip_address' => '192.168.1.101',
            'mac_address' => 'AA:BB:CC:DD:01:01',
            'pppoe_username' => 'john_a',
            'status' => 'active',
            'balance' => -45.00,
            'credit_limit' => 100.00,
            'tax_exempt' => false,
            'discount' => 0,
            'billing_cycle' => 'monthly',
            'created_at' => '2024-01-15',
            'activated_at' => '2024-01-16',
            'last_updated' => '2024-02-20 14:30',
        ];
    }

    public function getInvoicesProperty(): array
    {
        return [
            ['id' => 1, 'number' => 'INV-2024-0156', 'amount' => 49.99, 'due_date' => '2024-03-15', 'status' => 'paid'],
            ['id' => 2, 'number' => 'INV-2024-0187', 'amount' => 49.99, 'due_date' => '2024-02-15', 'status' => 'paid'],
            ['id' => 3, 'number' => 'INV-2024-0201', 'amount' => 49.99, 'due_date' => '2024-01-15', 'status' => 'paid'],
        ];
    }

    public function getServicesProperty(): array
    {
        return [
            [
                'id' => 1,
                'plan' => 'Fiber 100 Mbps',
                'router' => 'Mikrotik-01',
                'ip' => '192.168.1.101',
                'mac' => 'AA:BB:CC:DD:01:01',
                'status' => 'active',
                'activated_at' => '2024-01-16',
                'data_used' => '245.8 GB',
                'data_limit' => 'Unlimited',
            ],
        ];
    }

    public function getActivityLogProperty(): array
    {
        return [
            ['action' => 'Payment received', 'description' => '$49.99 payment via credit card', 'time' => '2 hours ago', 'icon' => 'payment'],
            ['action' => 'Service active', 'description' => 'Customer service is active', 'time' => '2 days ago', 'icon' => 'check'],
            ['action' => 'Plan changed', 'description' => 'Upgraded from Fiber 50 to Fiber 100', 'time' => '1 week ago', 'icon' => 'upgrade'],
            ['action' => 'Invoice sent', 'description' => 'Invoice INV-2024-0156 sent via email', 'time' => '2 weeks ago', 'icon' => 'invoice'],
            ['action' => 'Account created', 'description' => 'Customer account was created', 'time' => '2 months ago', 'icon' => 'user'],
        ];
    }
}
