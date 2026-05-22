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
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('billing_enabled')->default(false);
            $table->timestamp('billing_disabled_at')->nullable();
            $table->foreignId('default_plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->enum('default_billing_cycle', ['monthly', 'quarterly', 'yearly'])->nullable();
            $table->unsignedSmallInteger('default_grace_period_days')->nullable();
            $table->enum('default_discount_type', ['none', 'fixed', 'percentage'])->default('none');
            $table->decimal('default_discount_amount', 10, 2)->default(0);
            $table->decimal('default_tax_percentage', 5, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'billing_enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
