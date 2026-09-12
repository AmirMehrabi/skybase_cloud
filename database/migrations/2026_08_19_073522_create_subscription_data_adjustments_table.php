<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('subscription_data_adjustments')) {
            Schema::create('subscription_data_adjustments', function (Blueprint $table): void {
                $table->id();
                $table->string('tenant_id');
                $table->foreignId('subscription_id');
                $table->foreignId('subscription_usage_cycle_id');
                $table->string('type');
                $table->bigInteger('bytes');
                $table->decimal('amount', 10, 2)->nullable();
                $table->string('currency', 10)->nullable();
                $table->string('status')->default('active');
                $table->string('reason')->nullable();
                $table->timestamp('expires_at');
                $table->foreignId('created_by')->nullable();
                $table->timestamps();
            });
        }

        $requiredColumns = [
            'id', 'tenant_id', 'subscription_id', 'subscription_usage_cycle_id',
            'type', 'bytes', 'amount', 'currency', 'status', 'reason',
            'expires_at', 'created_by', 'created_at', 'updated_at',
        ];

        if (! Schema::hasColumns('subscription_data_adjustments', $requiredColumns)) {
            throw new RuntimeException('The existing subscription_data_adjustments table is missing required columns. Review its schema before retrying; existing data has been preserved.');
        }

        foreach ([
            ['subscription_id', 'subscriptions', 'sub_data_adj_subscription_fk', 'cascade'],
            ['subscription_usage_cycle_id', 'subscription_usage_cycles', 'sub_data_adj_usage_cycle_fk', 'cascade'],
            ['created_by', 'users', 'sub_data_adj_created_by_fk', 'set null'],
            ['tenant_id', 'tenants', 'sub_data_adj_tenant_fk', 'cascade'],
        ] as [$column, $parentTable, $name, $onDelete]) {
            $foreignKey = collect(Schema::getForeignKeys('subscription_data_adjustments'))
                ->first(fn (array $foreignKey): bool => $foreignKey['columns'] === [$column]);

            if ($foreignKey !== null) {
                if ($foreignKey['foreign_table'] !== $parentTable
                    || $foreignKey['foreign_columns'] !== ['id']
                    || strtolower($foreignKey['on_delete']) !== $onDelete) {
                    throw new RuntimeException("Unexpected foreign key on subscription_data_adjustments.{$column}; review the existing constraint before retrying.");
                }

                continue;
            }

            Schema::table('subscription_data_adjustments', function (Blueprint $table) use ($column, $parentTable, $name, $onDelete): void {
                $table->foreign($column, $name)->references('id')->on($parentTable)->onDelete($onDelete);
            });
        }

        if (! Schema::hasIndex('subscription_data_adjustments', ['subscription_usage_cycle_id', 'status', 'expires_at'])) {
            Schema::table('subscription_data_adjustments', function (Blueprint $table): void {
                $table->index(['subscription_usage_cycle_id', 'status', 'expires_at'], 'sub_data_adj_cycle_status_exp_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_data_adjustments');
    }
};
