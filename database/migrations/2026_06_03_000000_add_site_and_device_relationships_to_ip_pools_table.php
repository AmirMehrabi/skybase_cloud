<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('ip_pools', 'site_id')) {
            Schema::table('ip_pools', function (Blueprint $table): void {
                $table->foreignId('site_id')
                    ->nullable()
                    ->after('router_id')
                    ->constrained('sites')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('ip_pools', 'all_devices')) {
            Schema::table('ip_pools', function (Blueprint $table): void {
                $table->boolean('all_devices')
                    ->default(false)
                    ->after('site_id');
            });
        }

        if (! Schema::hasTable('ip_pool_router')) {
            Schema::create('ip_pool_router', function (Blueprint $table): void {
                $table->id();
                $table->string('tenant_id')->nullable();
                $table->foreignId('ip_pool_id')->constrained('ip_pools')->cascadeOnDelete();
                $table->foreignId('router_id')->constrained('routers')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['ip_pool_id', 'router_id'], 'ip_pool_router_unique');
                $table->index(['tenant_id', 'router_id'], 'ip_pool_router_tenant_router_index');
                $table->index(['tenant_id', 'ip_pool_id'], 'ip_pool_router_tenant_pool_index');
            });
        }

        $this->backfillLegacySiteReferences();
        $this->backfillLegacyRouterReferences();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ip_pool_router')) {
            Schema::dropIfExists('ip_pool_router');
        }

        if (Schema::hasColumn('ip_pools', 'all_devices')) {
            Schema::table('ip_pools', function (Blueprint $table): void {
                $table->dropColumn('all_devices');
            });
        }

        if (Schema::hasColumn('ip_pools', 'site_id')) {
            Schema::table('ip_pools', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('site_id');
            });
        }
    }

    private function backfillLegacySiteReferences(): void
    {
        if (! Schema::hasTable('sites') || ! Schema::hasColumn('ip_pools', 'site_id')) {
            return;
        }

        $sitesByTenant = DB::table('sites')
            ->select('id', 'tenant_id', 'name', 'code')
            ->orderBy('id')
            ->get()
            ->groupBy('tenant_id');

        $pools = DB::table('ip_pools')
            ->select('id', 'tenant_id', 'site', 'site_id')
            ->whereNull('site_id')
            ->whereNotNull('site')
            ->where('site', '!=', '')
            ->get();

        foreach ($pools as $pool) {
            $sites = $sitesByTenant->get($pool->tenant_id, collect());

            $site = $sites->first(function (object $site) use ($pool): bool {
                return strcasecmp((string) $site->name, (string) $pool->site) === 0
                    || strcasecmp((string) $site->code, (string) $pool->site) === 0;
            });

            if (! $site) {
                continue;
            }

            DB::table('ip_pools')
                ->where('id', $pool->id)
                ->update(['site_id' => $site->id]);
        }
    }

    private function backfillLegacyRouterReferences(): void
    {
        if (! Schema::hasTable('ip_pool_router') || ! Schema::hasColumn('ip_pools', 'router_id')) {
            return;
        }

        $existingAssignments = DB::table('ip_pool_router')
            ->select('ip_pool_id', 'router_id')
            ->get()
            ->mapWithKeys(fn (object $assignment): array => [
                $assignment->ip_pool_id.'-'.$assignment->router_id => true,
            ]);

        $rows = DB::table('ip_pools')
            ->select('id', 'tenant_id', 'router_id')
            ->whereNotNull('router_id')
            ->get();

        $now = now();
        $insert = [];

        foreach ($rows as $row) {
            $key = $row->id.'-'.$row->router_id;

            if (isset($existingAssignments[$key])) {
                continue;
            }

            $insert[] = [
                'tenant_id' => $row->tenant_id,
                'ip_pool_id' => $row->id,
                'router_id' => $row->router_id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($insert !== []) {
            DB::table('ip_pool_router')->insert($insert);
        }
    }
};
