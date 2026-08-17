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
        Schema::create('user_groups', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'name']);
        });

        foreach ($this->groupOwnedTables() as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('user_group_id')
                    ->nullable()
                    ->index()
                    ->constrained('user_groups')
                    ->restrictOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (array_reverse($this->groupOwnedTables()) as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('user_group_id');
            });
        }

        Schema::dropIfExists('user_groups');
    }

    /** @return list<string> */
    private function groupOwnedTables(): array
    {
        return [
            'users',
            'organizations',
            'customers',
            'subscriptions',
            'subscription_items',
            'subscription_ip_routes',
            'subscription_bandwidth_states',
            'invoices',
            'invoice_items',
            'payments',
            'customer_credits',
            'customer_notes',
            'tickets',
            'ticket_attachments',
            'ticket_events',
            'ticket_messages',
            'work_orders',
            'work_order_appointments',
            'work_order_attachments',
            'work_order_events',
            'work_order_materials',
            'work_order_notes',
            'work_order_tasks',
            'sites',
            'routers',
            'access_points',
            'ip_pools',
            'ip_addresses',
            'vpn_users',
            'router_monitoring_states',
            'netflow_flows',
            'network_alerts',
            'network_bandwidth_samples',
            'network_usage_records',
            'radacct',
            'radcheck',
            'radgroupreply',
            'radpostauth',
            'radreply',
            'radusergroup',
            'activity_log',
            'activity_logs',
            'notifications',
            'import_export_runs',
            'import_export_run_rows',
            'bulk_deletion_runs',
        ];
    }
};
