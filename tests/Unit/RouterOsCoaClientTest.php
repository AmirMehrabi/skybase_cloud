<?php

namespace Tests\Unit;

use App\Models\Router;
use App\Services\RouterOs\RouterOsCoaClient;
use ReflectionMethod;
use Tests\TestCase;

class RouterOsCoaClientTest extends TestCase
{
    public function test_disconnect_packet_includes_the_active_session_attributes(): void
    {
        $client = new RouterOsCoaClient;
        $router = new Router([
            'ip_address' => '192.168.88.1',
            'name' => 'Landing Station',
        ]);

        $reflection = new ReflectionMethod($client, 'buildDisconnectRequest');
        $reflection->setAccessible(true);

        [$packet] = $reflection->invoke(
            $client,
            $router,
            'john',
            '8120002f',
            '192.168.88.1',
            '10.10.10.55',
            'VeryStrongRadiusSecret',
        );

        $attributes = $this->decodeRadiusAttributes($packet);

        $this->assertSame('john', $attributes[1]);
        $this->assertSame('8120002f', $attributes[44]);
        $this->assertSame('192.168.88.1', $attributes[4]);
        $this->assertSame('10.10.10.55', $attributes[8]);
        $this->assertArrayNotHasKey(32, $attributes);
        $this->assertArrayHasKey(80, $attributes);
    }

    /**
     * @return array<int, string>
     */
    private function decodeRadiusAttributes(string $packet): array
    {
        $attributes = [];
        $length = unpack('n', substr($packet, 2, 2))[1];
        $offset = 20;

        while ($offset + 2 <= $length) {
            $type = ord($packet[$offset]);
            $attributeLength = ord($packet[$offset + 1]);

            if ($attributeLength < 2 || $offset + $attributeLength > $length) {
                break;
            }

            $value = substr($packet, $offset + 2, $attributeLength - 2);

            if (in_array($type, [4, 8], true)) {
                $value = inet_ntop($value) ?: '';
            }

            $attributes[$type] = $value;
            $offset += $attributeLength;
        }

        return $attributes;
    }
}
