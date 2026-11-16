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
        Schema::create('sbuk_sync_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('router_id')->constrained()->restrictOnDelete();
            $table->foreignId('ip_pool_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('pending');
            $table->unsignedInteger('total_records')->default(0);
            $table->unsignedInteger('valid_records')->default(0);
            $table->unsignedInteger('processed_records')->default(0);
            $table->unsignedInteger('created_customers')->default(0);
            $table->unsignedInteger('matched_customers')->default(0);
            $table->unsignedInteger('created_subscriptions')->default(0);
            $table->unsignedInteger('skipped_subscriptions')->default(0);
            $table->unsignedInteger('failed_records')->default(0);
            $table->text('error_summary')->nullable();
            $table->longText('personnel_snapshot')->nullable();
            $table->json('preview_summary')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status', 'created_at']);
        });

        Schema::create('sbuk_personnel_identities', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->char('identity_hash', 64);
            $table->string('external_index')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'identity_hash']);
            $table->unique(['tenant_id', 'customer_id']);
        });

        Schema::create('sbuk_sync_run_records', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignId('sbuk_sync_run_id')->constrained('sbuk_sync_runs')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->char('identity_hash', 64)->nullable();
            $table->string('outcome');
            $table->string('detail')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'sbuk_sync_run_id']);
            $table->unique(['sbuk_sync_run_id', 'row_number']);
            $table->index(['sbuk_sync_run_id', 'outcome']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sbuk_sync_run_records');
        Schema::dropIfExists('sbuk_personnel_identities');
        Schema::dropIfExists('sbuk_sync_runs');
    }
};
