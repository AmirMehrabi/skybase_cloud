<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class CustomersCreate extends Component
{
    // Section 1 - Basic Information
    public string $customerType = 'individual';

    public string $firstName = '';

    public string $lastName = '';

    public string $companyName = '';

    public string $nationalId = '';

    public string $registrationNumber = '';

    public string $dateOfBirth = '';

    // Section 2 - Contact Information
    public string $email = '';

    public string $phone = '';

    public string $mobile = '';

    public string $secondaryPhone = '';

    public string $whatsappNumber = '';

    // Section 3 - Address Information
    public string $addressLine1 = '';

    public string $addressLine2 = '';

    public string $city = '';

    public string $state = '';

    public string $postalCode = '';

    public string $country = 'United States';

    public string $latitude = '';

    public string $longitude = '';

    // Section 4 - Service Assignment
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

    // Section 5 - Financial Settings
    public float $initialBalance = 0;

    public float $creditLimit = 0;

    public bool $taxExempt = false;

    public float $discount = 0;

    public string $notes = '';

    // Section 6 - Status
    public string $status = 'pending';

    public bool $autoActivate = false;

    public function mount(): void
    {
        $this->activationDate = now()->format('Y-m-d');
    }

    public function save(): void
    {
        // Validate
        $this->validate();

        // Save logic would go here
        session()->flash('success', 'Customer created successfully.');
        $this->redirect(route('customers.index'), navigate: true);
    }

    public function saveDraft(): void
    {
        session()->flash('success', 'Customer saved as draft.');
        $this->redirect(route('customers.index'), navigate: true);
    }

    public function generatePPPoE(): void
    {
        $this->pppoeUsername = strtolower(str()->random(8));
        $this->pppoePassword = str()->random(12);
    }

    protected function rules(): array
    {
        return [
            'firstName' => 'required_if:customerType,individual',
            'lastName' => 'required_if:customerType,individual',
            'companyName' => 'required_if:customerType,business',
            'email' => 'required|email',
            'mobile' => 'required',
            'plan' => 'required',
            'site' => 'required',
            'router' => 'required',
        ];
    }
}
