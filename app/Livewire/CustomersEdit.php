<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class CustomersEdit extends Component
{
    public int $customerId;

    // Same fields as Create
    public string $customerType = 'individual';

    public string $firstName = '';

    public string $lastName = '';

    public string $companyName = '';

    public string $nationalId = '';

    public string $registrationNumber = '';

    public string $dateOfBirth = '';

    public string $email = '';

    public string $phone = '';

    public string $mobile = '';

    public string $secondaryPhone = '';

    public string $whatsappNumber = '';

    public string $addressLine1 = '';

    public string $addressLine2 = '';

    public string $city = '';

    public string $state = '';

    public string $postalCode = '';

    public string $country = 'United States';

    public string $latitude = '';

    public string $longitude = '';

    public string $plan = '';

    public string $site = '';

    public string $router = '';

    public string $ipPool = '';

    public ?bool $staticIp = false;

    public string $macAddress = '';

    public string $pppoeUsername = '';

    public string $pppoePassword = '';

    public string $activationDate = '';

    public string $billingCycle = 'monthly';

    public float $initialBalance = 0;

    public float $creditLimit = 0;

    public bool $taxExempt = false;

    public float $discount = 0;

    public string $notes = '';

    public string $status = 'pending';

    public function mount(int $id): void
    {
        $this->customerId = $id;
        $this->loadCustomer();
    }

    private function loadCustomer(): void
    {
        // Dummy customer data - in real app, load from DB
        $customer = $this->getDummyCustomer($this->customerId);

        $this->customerType = $customer['type'] ?? 'individual';
        $this->firstName = $customer['first_name'] ?? '';
        $this->lastName = $customer['last_name'] ?? '';
        $this->companyName = $customer['company_name'] ?? '';
        $this->nationalId = $customer['national_id'] ?? '';
        $this->registrationNumber = $customer['registration_number'] ?? '';
        $this->email = $customer['email'] ?? '';
        $this->phone = $customer['phone'] ?? '';
        $this->mobile = $customer['mobile'] ?? '';
        $this->plan = $customer['plan'] ?? '';
        $this->site = $customer['site'] ?? '';
        $this->router = $customer['router'] ?? '';
        $this->status = $customer['status'] ?? 'pending';
        $this->macAddress = $customer['mac_address'] ?? '';
        $this->pppoeUsername = $customer['pppoe_username'] ?? '';
        $this->pppoePassword = $customer['pppoe_password'] ?? '';
        $this->initialBalance = $customer['balance'] ?? 0;
        $this->creditLimit = $customer['credit_limit'] ?? 0;
    }

    private function getDummyCustomer(int $id): array
    {
        return [
            'id' => $id,
            'type' => 'individual',
            'first_name' => 'John',
            'last_name' => 'Anderson',
            'email' => 'john.anderson@email.com',
            'phone' => '+1 (555) 123-4567',
            'mobile' => '+1 (555) 987-6543',
            'plan' => 'Fiber 100 Mbps',
            'site' => 'Downtown Hub',
            'router' => 'Mikrotik-01',
            'status' => 'active',
            'mac_address' => 'AA:BB:CC:DD:01:01',
            'pppoe_username' => 'john_a',
            'pppoe_password' => 'Secret123',
            'balance' => -45.00,
            'credit_limit' => 100.00,
        ];
    }

    public function update(): void
    {
        session()->flash('success', 'Customer updated successfully.');
        $this->redirect(route('customers.index'), navigate: true);
    }

    public function suspendService(): void
    {
        $this->status = 'suspended';
        session()->flash('success', 'Service suspended successfully.');
    }

    public function resetPPPoE(): void
    {
        $this->pppoePassword = str()->random(12);
        session()->flash('success', 'PPPoE password has been reset.');
    }
}
