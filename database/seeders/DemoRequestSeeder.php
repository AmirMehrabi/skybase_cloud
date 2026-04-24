<?php

namespace Database\Seeders;

use App\Models\DemoRequest;
use Illuminate\Database\Seeder;

class DemoRequestSeeder extends Seeder
{
    public function run(): void
    {
        DemoRequest::factory()->count(10)->create();
    }
}
