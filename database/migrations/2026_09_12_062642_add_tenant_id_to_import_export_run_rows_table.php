<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection(config('tenancy.database.central_connection'));

        if (! $schema->hasColumn('import_export_run_rows', 'tenant_id')) {
            $schema->table('import_export_run_rows', function (Blueprint $table): void {
                $table->string('tenant_id')->nullable();
            });
        }

        if (! $schema->hasIndex('import_export_run_rows', 'import_export_rows_tenant_run_index')) {
            $schema->table('import_export_run_rows', function (Blueprint $table): void {
                $table->index(['tenant_id', 'import_export_run_id'], 'import_export_rows_tenant_run_index');
            });
        }

        $connection = $schema->getConnection();

        $connection->table('tenants')->select('id')->chunkById(100, function (Collection $tenants) use ($connection): void {
            foreach ($tenants as $tenant) {
                $connection->table('import_export_runs')
                    ->where('tenant_id', $tenant->id)
                    ->select('id')
                    ->chunkById(500, function (Collection $runs) use ($connection, $tenant): void {
                        $connection->table('import_export_run_rows')
                            ->whereIn('import_export_run_id', $runs->pluck('id'))
                            ->whereNull('tenant_id')
                            ->update(['tenant_id' => $tenant->id]);
                    });
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection(config('tenancy.database.central_connection'));

        if (! $schema->hasColumn('import_export_run_rows', 'tenant_id')) {
            return;
        }

        if ($schema->hasIndex('import_export_run_rows', 'import_export_rows_tenant_run_index')) {
            $schema->table('import_export_run_rows', function (Blueprint $table): void {
                $table->dropIndex('import_export_rows_tenant_run_index');
            });
        }

        $schema->table('import_export_run_rows', function (Blueprint $table): void {
            $table->dropColumn('tenant_id');
        });
    }
};
