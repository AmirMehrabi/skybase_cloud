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
        Schema::table('plans', function (Blueprint $table) {
            $table->string('tenant_id')->nullable()->after('id');
            $table->index('tenant_id');
        });

        $fallbackTenantId = DB::table('tenants')->orderBy('created_at')->value('id');

        DB::table('plans')->orderBy('id')->get()->each(function (object $plan) use ($fallbackTenantId): void {
            $tenantIds = DB::table('subscriptions')
                ->where('plan_id', $plan->id)
                ->distinct()
                ->pluck('tenant_id')
                ->filter()
                ->values();

            $ownerTenantId = $tenantIds->first() ?? $fallbackTenantId;
            if ($ownerTenantId === null) {
                return;
            }

            DB::table('plans')->where('id', $plan->id)->update(['tenant_id' => $ownerTenantId]);

            $tenantIds->skip(1)->each(function (string $tenantId) use ($plan): void {
                $attributes = (array) $plan;
                unset($attributes['id']);
                $attributes['tenant_id'] = $tenantId;
                $attributes['internal_name'] = $attributes['internal_name'].'-'.$tenantId;
                $duplicateId = DB::table('plans')->insertGetId($attributes);

                DB::table('subscriptions')
                    ->where('tenant_id', $tenantId)
                    ->where('plan_id', $plan->id)
                    ->update(['plan_id' => $duplicateId]);
            });
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropUnique('plans_internal_name_unique');
            $table->unique(['tenant_id', 'internal_name']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE subscriptions MODIFY billing_cycle ENUM('daily','weekly','monthly','quarterly','yearly') NOT NULL DEFAULT 'monthly'");
            DB::statement("ALTER TABLE subscription_items MODIFY billing_cycle ENUM('daily','weekly','monthly','quarterly','yearly','onetime') NOT NULL DEFAULT 'monthly'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropUnique(['tenant_id', 'internal_name']);
            $table->unique('internal_name');
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};
