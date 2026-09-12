<?php

namespace Tests\Feature;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ImportExportRunRowMigrationTest extends TestCase
{
    private Builder $schema;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.migration_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'tenancy.database.central_connection' => 'migration_test',
        ]);
        $this->schema = Schema::connection('migration_test');
        $this->schema->create('tenants', function (Blueprint $table): void {
            $table->string('id')->primary();
        });
        $this->schema->create('import_export_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
        });
        $this->schema->create('import_export_run_rows', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('import_export_run_id');
            $table->text('payload');
        });
    }

    public function test_backfill_preserves_rows_and_assigns_each_parents_tenant_and_can_be_retried(): void
    {
        $connection = $this->schema->getConnection();
        $connection->table('tenants')->insert([['id' => 'tenant-a'], ['id' => 'tenant-b']]);
        $connection->table('import_export_runs')->insert([
            ['id' => 1, 'tenant_id' => 'tenant-a'],
            ['id' => 2, 'tenant_id' => 'tenant-b'],
        ]);
        $connection->table('import_export_run_rows')->insert([
            ['id' => 1, 'import_export_run_id' => 1, 'payload' => 'original-a'],
            ['id' => 2, 'import_export_run_id' => 2, 'payload' => 'original-b'],
            ['id' => 3, 'import_export_run_id' => 999, 'payload' => 'orphan'],
        ]);

        $migration = $this->migration();
        $migration->up();
        $migration->up();

        $this->assertSame('original-a', $connection->table('import_export_run_rows')->where('tenant_id', 'tenant-a')->value('payload'));
        $this->assertSame('original-b', $connection->table('import_export_run_rows')->where('tenant_id', 'tenant-b')->value('payload'));
        $this->assertSame('orphan', $connection->table('import_export_run_rows')->whereNull('tenant_id')->value('payload'));
        $this->assertTrue($this->schema->hasIndex('import_export_run_rows', 'import_export_rows_tenant_run_index'));

        $migration->down();
        $this->assertFalse($this->schema->hasColumn('import_export_run_rows', 'tenant_id'));
        $this->assertSame(3, $connection->table('import_export_run_rows')->count());
        $migration->down();
    }

    public function test_partial_migration_preserves_already_assigned_tenants(): void
    {
        $this->schema->table('import_export_run_rows', function (Blueprint $table): void {
            $table->string('tenant_id')->nullable();
        });
        $connection = $this->schema->getConnection();
        $connection->table('tenants')->insert(['id' => 'tenant-a']);
        $connection->table('import_export_runs')->insert(['id' => 1, 'tenant_id' => 'tenant-a']);
        $connection->table('import_export_run_rows')->insert([
            'id' => 1, 'import_export_run_id' => 1, 'payload' => 'original', 'tenant_id' => 'tenant-a',
        ]);

        $this->migration()->up();

        $this->assertSame('original', $connection->table('import_export_run_rows')->where('tenant_id', 'tenant-a')->value('payload'));
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_09_12_062642_add_tenant_id_to_import_export_run_rows_table.php');
    }
}
