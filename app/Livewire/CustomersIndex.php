<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class CustomersIndex extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $status = '';

    #[Url(history: true)]
    public string $plan = '';

    #[Url(history: true)]
    public string $site = '';

    #[Url(history: true)]
    public string $router = '';

    public int $perPage = 10;

    public function render(): \Illuminate\View\View
    {
        $customers = $this->getDummyCustomers();

        // Apply filters
        if ($this->search) {
            $customers = $this->filterBySearch($customers);
        }

        if ($this->status) {
            $customers = array_filter($customers, fn ($c) => $c['status'] === $this->status);
        }

        if ($this->plan) {
            $customers = array_filter($customers, fn ($c) => $c['plan'] === $this->plan);
        }

        if ($this->site) {
            $customers = array_filter($customers, fn ($c) => $c['site'] === $this->site);
        }

        if ($this->router) {
            $customers = array_filter($customers, fn ($c) => $c['router'] === $this->router);
        }

        // Pagination
        $currentPage = $this->getPage();
        $total = count($customers);
        $customers = array_slice($customers, ($currentPage - 1) * $this->perPage, $this->perPage);

        return view('livewire.customers-index', [
            'customers' => $customers,
            'stats' => $this->getStats(),
            'totalCustomers' => $total,
        ]);
    }

    private function getDummyCustomers(): array
    {
        return [
            [
                'id' => 1,
                'customer_code' => 'CUS-2024-0001',
                'type' => 'individual',
                'name' => 'John Anderson',
                'company' => null,
                'email' => 'john.anderson@email.com',
                'phone' => '+1 (555) 123-4567',
                'mobile' => '+1 (555) 987-6543',
                'plan' => 'Fiber 100 Mbps',
                'site' => 'Downtown Hub',
                'router' => 'Mikrotik-01',
                'ip_address' => '192.168.1.101',
                'mac_address' => 'AA:BB:CC:DD:01:01',
                'status' => 'active',
                'balance' => -45.00,
                'credit_limit' => 100.00,
                'created_at' => '2024-01-15',
                'activated_at' => '2024-01-16',
            ],
            [
                'id' => 2,
                'customer_code' => 'CUS-2024-0002',
                'type' => 'business',
                'name' => 'Tech Solutions Inc',
                'company' => 'Tech Solutions Inc',
                'email' => 'it@techsolutions.com',
                'phone' => '+1 (555) 234-5678',
                'mobile' => '+1 (555) 876-5432',
                'plan' => 'Fiber 500 Mbps',
                'site' => 'Business Park',
                'router' => 'Mikrotik-02',
                'ip_address' => '192.168.2.50',
                'mac_address' => 'AA:BB:CC:DD:02:01',
                'status' => 'active',
                'balance' => 0.00,
                'credit_limit' => 500.00,
                'created_at' => '2024-02-01',
                'activated_at' => '2024-02-02',
            ],
            [
                'id' => 3,
                'customer_code' => 'CUS-2024-0003',
                'type' => 'individual',
                'name' => 'Sarah Mitchell',
                'company' => null,
                'email' => 'sarah.m@email.com',
                'phone' => '+1 (555) 345-6789',
                'mobile' => '+1 (555) 765-4321',
                'plan' => 'Fiber 50 Mbps',
                'site' => 'West Station',
                'router' => 'Cisco-01',
                'ip_address' => '192.168.3.201',
                'mac_address' => 'AA:BB:CC:DD:03:01',
                'status' => 'suspended',
                'balance' => 120.50,
                'credit_limit' => 50.00,
                'created_at' => '2024-02-10',
                'activated_at' => '2024-02-11',
            ],
            [
                'id' => 4,
                'customer_code' => 'CUS-2024-0004',
                'type' => 'individual',
                'name' => 'Michael Chen',
                'company' => null,
                'email' => 'mchen@email.com',
                'phone' => '+1 (555) 456-7890',
                'mobile' => '+1 (555) 654-3210',
                'plan' => 'Fiber 200 Mbps',
                'site' => 'North Tower',
                'router' => 'Mikrotik-03',
                'ip_address' => '192.168.4.85',
                'mac_address' => 'AA:BB:CC:DD:04:01',
                'status' => 'active',
                'balance' => -60.00,
                'credit_limit' => 150.00,
                'created_at' => '2024-02-15',
                'activated_at' => '2024-02-16',
            ],
            [
                'id' => 5,
                'customer_code' => 'CUS-2024-0005',
                'type' => 'business',
                'name' => 'Global Marketing Ltd',
                'company' => 'Global Marketing Ltd',
                'email' => 'admin@globalmarketing.com',
                'phone' => '+1 (555) 567-8901',
                'mobile' => '+1 (555) 543-2109',
                'plan' => 'Fiber 1 Gbps',
                'site' => 'Business Park',
                'router' => 'Cisco-02',
                'ip_address' => '192.168.2.100',
                'mac_address' => 'AA:BB:CC:DD:05:01',
                'status' => 'active',
                'balance' => 0.00,
                'credit_limit' => 1000.00,
                'created_at' => '2024-03-01',
                'activated_at' => '2024-03-02',
            ],
            [
                'id' => 6,
                'customer_code' => 'CUS-2024-0006',
                'type' => 'individual',
                'name' => 'Emma Thompson',
                'company' => null,
                'email' => 'emma.t@email.com',
                'phone' => '+1 (555) 678-9012',
                'mobile' => '+1 (555) 432-1098',
                'plan' => 'Fiber 100 Mbps',
                'site' => 'Downtown Hub',
                'router' => 'Mikrotik-01',
                'ip_address' => '192.168.1.102',
                'mac_address' => 'AA:BB:CC:DD:06:01',
                'status' => 'pending',
                'balance' => 0.00,
                'credit_limit' => 100.00,
                'created_at' => '2024-03-05',
                'activated_at' => null,
            ],
            [
                'id' => 7,
                'customer_code' => 'CUS-2024-0007',
                'type' => 'individual',
                'name' => 'David Martinez',
                'company' => null,
                'email' => 'd.martinez@email.com',
                'phone' => '+1 (555) 789-0123',
                'mobile' => '+1 (555) 321-0987',
                'plan' => 'Fiber 50 Mbps',
                'site' => 'South Station',
                'router' => 'Mikrotik-04',
                'ip_address' => '192.168.5.150',
                'mac_address' => 'AA:BB:CC:DD:07:01',
                'status' => 'terminated',
                'balance' => 25.00,
                'credit_limit' => 50.00,
                'created_at' => '2024-01-20',
                'activated_at' => '2024-01-21',
            ],
            [
                'id' => 8,
                'customer_code' => 'CUS-2024-0008',
                'type' => 'business',
                'name' => 'Creative Design Studio',
                'company' => 'Creative Design Studio',
                'email' => 'support@creativedesign.com',
                'phone' => '+1 (555) 890-1234',
                'mobile' => '+1 (555) 210-9876',
                'plan' => 'Fiber 200 Mbps',
                'site' => 'North Tower',
                'router' => 'Cisco-03',
                'ip_address' => '192.168.4.86',
                'mac_address' => 'AA:BB:CC:DD:08:01',
                'status' => 'active',
                'balance' => -80.00,
                'credit_limit' => 200.00,
                'created_at' => '2024-02-20',
                'activated_at' => '2024-02-21',
            ],
            [
                'id' => 9,
                'customer_code' => 'CUS-2024-0009',
                'type' => 'individual',
                'name' => 'Lisa Wang',
                'company' => null,
                'email' => 'lwang@email.com',
                'phone' => '+1 (555) 901-2345',
                'mobile' => '+1 (555) 109-8765',
                'plan' => 'Fiber 100 Mbps',
                'site' => 'East Center',
                'router' => 'Mikrotik-05',
                'ip_address' => '192.168.6.75',
                'mac_address' => 'AA:BB:CC:DD:09:01',
                'status' => 'suspended',
                'balance' => 95.00,
                'credit_limit' => 100.00,
                'created_at' => '2024-03-10',
                'activated_at' => '2024-03-11',
            ],
            [
                'id' => 10,
                'customer_code' => 'CUS-2024-0010',
                'type' => 'individual',
                'name' => 'Robert Taylor',
                'company' => null,
                'email' => 'r.taylor@email.com',
                'phone' => '+1 (555) 012-3456',
                'mobile' => '+1 (555) 098-7654',
                'plan' => 'Fiber 500 Mbps',
                'site' => 'West Station',
                'router' => 'Mikrotk-06',
                'ip_address' => '192.168.3.202',
                'mac_address' => 'AA:BB:CC:DD:10:01',
                'status' => 'active',
                'balance' => 0.00,
                'credit_limit' => 200.00,
                'created_at' => '2024-03-12',
                'activated_at' => '2024-03-13',
            ],
        ];
    }

    private function filterBySearch(array $customers): array
    {
        $search = strtolower($this->search);

        return array_filter($customers, function ($customer) use ($search) {
            return str_contains(strtolower($customer['name']), $search)
                || str_contains(strtolower($customer['customer_code']), $search)
                || str_contains(strtolower($customer['email']), $search)
                || str_contains(strtolower($customer['phone']), $search);
        });
    }

    private function getStats(): array
    {
        return [
            'total' => 156,
            'active' => 142,
            'suspended' => 8,
            'overdue' => 6,
        ];
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status', 'plan', 'site', 'router']);
        $this->resetPage();
    }
}
