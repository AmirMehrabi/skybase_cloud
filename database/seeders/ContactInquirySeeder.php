<?php

namespace Database\Seeders;

use App\Models\ContactInquiry;
use Illuminate\Database\Seeder;

class ContactInquirySeeder extends Seeder
{
    public function run(): void
    {
        ContactInquiry::factory()->count(10)->create();
    }
}
