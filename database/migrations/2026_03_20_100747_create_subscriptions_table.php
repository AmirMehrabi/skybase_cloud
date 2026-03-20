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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');

            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('subscription_code')->unique();

            // Service assignment
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('router_id')->nullable()->constrained()->nullOnDelete();
            $table->string('site')->nullable();
            $table->string('ip_address')->nullable();

            // PPPoE credentials
            $table->string('pppoe_username')->nullable();
            $table->string('pppoe_password')->nullable();

            // Pricing
            $table->decimal('base_price', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->string('discount_type')->default('none'); // none, fixed, percentage
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2)->default(0);

            // Billing
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'yearly'])->default('monthly');

            // Status and dates
            $table->enum('status', ['pending', 'active', 'suspended', 'cancelled'])->default('pending');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamp('activation_date')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Notes
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
