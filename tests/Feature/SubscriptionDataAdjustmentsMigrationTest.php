<?php

namespace Tests\Feature;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SubscriptionDataAdjustmentsMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'migration_test',
            'database.connections.migration_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true],
        ]);

        foreach (['subscriptions', 'subscription_usage_cycles', 'users'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table): void {
                $table->id();
            });
        }

        Schema::create('tenants', function (Blueprint $table): void {
            $table->string('id')->primary();
        });
    }

    public function test_fresh_migration_can_run_twice(): void
    {
        $this->migration()->up();
        $this->migration()->up();

        $this->assertCount(4, Schema::getForeignKeys('subscription_data_adjustments'));
        $this->assertTrue(Schema::hasIndex('subscription_data_adjustments', ['subscription_usage_cycle_id', 'status', 'expires_at']));
    }

    public function test_partial_table_is_completed_without_losing_data(): void
    {
        $this->migration()->up();
        foreach (['subscription_usage_cycle_id', 'created_by', 'tenant_id'] as $name) {
            Schema::table('subscription_data_adjustments', function (Blueprint $table) use ($name): void {
                $table->dropForeign([$name]);
            });
        }
        Schema::table('subscription_data_adjustments', function (Blueprint $table): void {
            $table->dropIndex('sub_data_adj_cycle_status_exp_idx');
        });

        $connection = Schema::getConnection();
        $connection->table('tenants')->insert(['id' => 'tenant-a']);
        $connection->table('subscriptions')->insert(['id' => 1]);
        $connection->table('subscription_usage_cycles')->insert(['id' => 1]);
        $connection->table('subscription_data_adjustments')->insert([
            'tenant_id' => 'tenant-a', 'subscription_id' => 1,
            'subscription_usage_cycle_id' => 1, 'type' => 'bonus', 'bytes' => 1024,
            'expires_at' => '2026-12-31 00:00:00', 'reason' => 'Preserve this record',
        ]);

        $this->migration()->up();
        $this->migration()->up();

        $this->assertCount(4, Schema::getForeignKeys('subscription_data_adjustments'));
        $this->assertTrue(Schema::hasIndex('subscription_data_adjustments', ['subscription_usage_cycle_id', 'status', 'expires_at']));
        $this->assertSame('Preserve this record', $connection->table('subscription_data_adjustments')->where('tenant_id', 'tenant-a')->value('reason'));
    }

    public function test_incompatible_existing_table_is_not_silently_accepted(): void
    {
        Schema::create('subscription_data_adjustments', function (Blueprint $table): void {
            $table->id();
        });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('missing required columns');
        $this->migration()->up();
    }

    public function test_subscription_items_backfill_can_resume_without_overwriting_existing_tenants(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->string('tenant_id');
        });
        Schema::create('subscription_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('subscription_id');
            $table->string('tenant_id')->nullable();
        });
        $connection = Schema::getConnection();
        $connection->table('tenants')->insert([['id' => 'tenant-a'], ['id' => 'tenant-b']]);
        $connection->table('subscriptions')->insert(['id' => 1, 'tenant_id' => 'tenant-a']);
        $connection->table('subscription_items')->insert([
            ['id' => 1, 'subscription_id' => 1, 'tenant_id' => null],
            ['id' => 2, 'subscription_id' => 1, 'tenant_id' => 'tenant-b'],
        ]);
        $migration = require database_path('migrations/2026_08_21_194848_add_tenant_id_to_subscription_items_table.php');
        $migration->up();
        $migration->up();

        $this->assertSame(1, $connection->table('subscription_items')->where('tenant_id', 'tenant-a')->count());
        $this->assertSame(1, $connection->table('subscription_items')->where('tenant_id', 'tenant-b')->count());
        $this->assertCount(1, Schema::getForeignKeys('subscription_items'));
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_08_19_073522_create_subscription_data_adjustments_table.php');
    }
}
