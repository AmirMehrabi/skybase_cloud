<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('golestan_student_links', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->string('student_number', 64);
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->unique(['tenant_id', 'student_number']);
            $table->unique(['tenant_id', 'subscription_id']);
            $table->timestamps();
        });

        Schema::create('golestan_sync_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('preview_token');
            $table->unique(['tenant_id', 'preview_token']);
            $table->string('status')->default('pending');
            $table->unsignedInteger('total');
            $table->longText('snapshot')->nullable();
            $table->json('outcomes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('golestan_sync_runs');
        Schema::dropIfExists('golestan_student_links');
    }
};
