<?php

namespace Tests\Feature;

use App\Services\SkybasePackageCatalog;
use InvalidArgumentException;
use Tests\TestCase;

class SkybasePackageCatalogTest extends TestCase
{
    public function test_recommendations_respect_every_published_boundary(): void
    {
        $catalog = app(SkybasePackageCatalog::class);
        $previousLimit = -1;
        foreach ($catalog->all() as $key => $package) {
            $this->assertSame($key, $catalog->recommendedKey($previousLimit + 1));
            $this->assertSame($key, $catalog->recommendedKey($package['subscriber_limit']));
            $previousLimit = $package['subscriber_limit'];
        }
        $this->assertNull($catalog->recommendedKey(5001));
        $this->assertSame(6900, $catalog->get('starter')['monthly_price_minor']);
        $this->assertFalse(config('skybase_billing.enabled'));
    }

    public function test_unknown_packages_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(SkybasePackageCatalog::class)->get('forged');
    }

    public function test_negative_usage_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(SkybasePackageCatalog::class)->recommendedKey(-1);
    }
}
