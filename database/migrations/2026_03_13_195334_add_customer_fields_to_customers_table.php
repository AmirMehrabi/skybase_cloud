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
            // Basic identification
            $table->string('customer_code')->unique()->after('id');
            $table->enum('customer_type', ['individual', 'business'])->default('individual')->after('tenant_id');

            // Name fields for individual
            $table->string('first_name')->nullable()->after('customer_type');
            $table->string('last_name')->nullable()->after('first_name');

            // Business name
            $table->string('company_name')->nullable()->after('last_name');

            // National ID / SSN
            $table->string('national_id')->nullable()->after('name');

            // Additional contact
            $table->string('mobile')->nullable()->after('phone');
            $table->string('whatsapp')->nullable()->after('mobile');

            // Address fields - rename zip to postal_code, add address_line1/2
            $table->renameColumn('address', 'address_line1');
            $table->string('address_line2')->nullable()->after('address_line1');
            $table->renameColumn('zip', 'postal_code');

            // Service assignment
            $table->string('plan')->nullable()->after('status');
            $table->string('site')->nullable()->after('plan');
            $table->string('router')->nullable()->after('site');
            $table->string('ip_address')->nullable()->after('router');

            // PPPoE credentials
            $table->string('pppoe_username')->nullable()->after('ip_address');
            $table->string('pppoe_password')->nullable()->after('pppoe_username');

            // Billing
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'yearly'])->default('monthly')->after('billing_type');
            $table->decimal('credit_limit', 10, 2)->default(0)->after('balance');
            $table->boolean('tax_exempt')->default(false)->after('credit_limit');

            // Activation
            $table->timestamp('activation_date')->nullable()->after('created_at');

            // Modify status to include 'pending'
            $table->enum('status', ['pending', 'active', 'inactive', 'suspended'])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Drop new columns
            $table->dropColumn([
                'customer_code',
                'customer_type',
                'first_name',
                'last_name',
                'company_name',
                'national_id',
                'mobile',
                'whatsapp',
                'address_line2',
                'plan',
                'site',
                'router',
                'ip_address',
                'pppoe_username',
                'pppoe_password',
                'billing_cycle',
                'credit_limit',
                'tax_exempt',
                'activation_date',
            ]);

            // Rename back
            $table->renameColumn('address_line1', 'address');
            $table->renameColumn('postal_code', 'zip');

            // Revert status enum
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->change();
        });
    }
};
