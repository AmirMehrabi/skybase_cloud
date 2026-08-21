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
        if (! Schema::hasTable('subscription_items')) {
            return;
        }

        if (! Schema::hasColumn('subscription_items', 'tenant_id')) {
            Schema::table('subscription_items', function (Blueprint $table): void {
                $table->string('tenant_id')->nullable()->after('id');
            });
        }

        if (! $this->hasTenantIndex()) {
            Schema::table('subscription_items', function (Blueprint $table): void {
                $table->index('tenant_id');
            });
        }

        if (Schema::hasTable('subscriptions') && Schema::hasColumn('subscriptions', 'tenant_id')) {
            DB::table('subscription_items')
                ->select('subscription_items.id', 'subscriptions.tenant_id')
                ->join('subscriptions', 'subscriptions.id', '=', 'subscription_items.subscription_id')
                ->orderBy('subscription_items.id')
                ->chunkById(500, function ($items): void {
                    foreach ($items as $item) {
                        DB::table('subscription_items')
                            ->where('id', $item->id)
                            ->update(['tenant_id' => $item->tenant_id]);
                    }
                }, 'subscription_items.id', 'id');
        }

        if (Schema::hasTable('tenants') && ! $this->hasTenantForeignKey()) {
            Schema::table('subscription_items', function (Blueprint $table): void {
                $table->foreign('tenant_id')
                    ->references('id')
                    ->on('tenants')
                    ->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('subscription_items') || ! Schema::hasColumn('subscription_items', 'tenant_id')) {
            return;
        }

        $hasTenantForeignKey = $this->hasTenantForeignKey();
        $hasTenantIndex = $this->hasTenantIndex();

        Schema::table('subscription_items', function (Blueprint $table) use ($hasTenantForeignKey, $hasTenantIndex): void {
            if ($hasTenantForeignKey) {
                $table->dropForeign(['tenant_id']);
            }

            if ($hasTenantIndex) {
                $table->dropIndex(['tenant_id']);
            }

            $table->dropColumn('tenant_id');
        });
    }

    private function hasTenantForeignKey(): bool
    {
        return collect(Schema::getForeignKeys('subscription_items'))
            ->contains(fn (array $foreignKey): bool => $foreignKey['columns'] === ['tenant_id']);
    }

    private function hasTenantIndex(): bool
    {
        return collect(Schema::getIndexes('subscription_items'))
            ->contains(fn (array $index): bool => $index['columns'] === ['tenant_id']);
    }
};
