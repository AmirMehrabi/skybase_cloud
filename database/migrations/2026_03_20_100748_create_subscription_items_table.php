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
        Schema::create('subscription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');

            // Item details
            $table->string('item_type'); // plan, additional_service, setup_fee, etc.
            $table->string('item_code')->nullable(); // Plan code or service code
            $table->string('description');
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('router_id')->nullable()->constrained()->nullOnDelete();

            // Quantity (default 1, can be used for multiple IPs, etc.)
            $table->integer('quantity')->default(1);

            // Pricing
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->string('discount_type')->default('none'); // none, fixed, percentage
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2)->default(0); // (unit_price * quantity) - discount
            $table->decimal('total', 10, 2)->default(0); // subtotal + tax

            // Recurring vs one-time
            $table->boolean('recurring')->default(true);
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'yearly', 'onetime'])->default('monthly');

            // Dates
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_items');
    }
};
