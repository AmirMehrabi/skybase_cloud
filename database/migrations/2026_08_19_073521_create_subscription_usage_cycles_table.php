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
        Schema::create('subscription_usage_cycles', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->unsignedBigInteger('allowance_bytes')->nullable();
            $table->unsignedBigInteger('used_upload_bytes')->default(0);
            $table->unsignedBigInteger('used_download_bytes')->default(0);
            $table->unsignedBigInteger('last_accounted_bytes')->default(0);
            $table->timestamp('quota_reached_at')->nullable();
            $table->timestamp('exempt_until')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['subscription_id', 'starts_at']);
            $table->index(['tenant_id', 'ends_at', 'closed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_usage_cycles');
    }
};
