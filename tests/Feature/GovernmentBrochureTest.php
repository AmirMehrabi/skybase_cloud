<?php

namespace Tests\Feature;

use Tests\TestCase;

class GovernmentBrochureTest extends TestCase
{
    public function test_government_brochure_is_public_and_contains_core_farsi_positioning(): void
    {
        $response = $this->get(route('brochures.government-fa'));

        $response
            ->assertOk()
            ->assertSee('SkyBase')
            ->assertSee('نهادهای دولتی')
            ->assertSee('صورتحساب')
            ->assertSee('همگام‌سازی Active Directory')
            ->assertSee('مدیریت گروه‌ها');
    }
}
