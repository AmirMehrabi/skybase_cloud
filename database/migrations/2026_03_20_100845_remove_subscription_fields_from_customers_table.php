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
        Schema::table('customers', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['plan_id']);
            $table->dropForeign(['router_id']);

            // Drop subscription-related columns
            $table->dropColumn([
                'plan',
                'plan_id',
                'site',
                'router',
                'router_id',
                'ip_address',
                'pppoe_username',
                'pppoe_password',
                'billing_cycle',
                'activation_date',
                'status',
            ]);

            // Add back a simple status for customer account
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->after('tax_exempt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Remove the new simple status
            $table->dropColumn('status');

            // Add back subscription-related columns
            $table->string('plan')->nullable()->after('country');
            $table->foreignId('plan_id')->nullable()->after('plan')->constrained()->nullOnDelete();
            $table->string('site')->nullable()->after('plan_id');
            $table->string('router')->nullable()->after('site');
            $table->foreignId('router_id')->nullable()->after('router')->constrained()->nullOnDelete();
            $table->string('ip_address')->nullable()->after('router_id');
            $table->string('pppoe_username')->nullable()->after('ip_address');
            $table->string('pppoe_password')->nullable()->after('pppoe_username');
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'yearly'])->default('monthly')->after('billing_type');
            $table->timestamp('activation_date')->nullable()->after('created_at');
        });
    }
};
