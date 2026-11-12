<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->boolean('acs_enabled')->default(false);
        });
        Schema::create('acs_connections', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->unique();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('base_url')->unique();
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->string('cwmp_url')->nullable();
            $table->boolean('automation_enabled')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->string('health')->default('untested');
            $table->timestamps();
        });
        Schema::create('provisioning_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('manufacturer');
            $table->string('model');
            $table->unsignedInteger('revision')->default(1);
            $table->text('parameters');
            $table->timestamps();
        });
        Schema::create('customer_devices', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignId('acs_connection_id')->constrained()->cascadeOnDelete();
            $table->string('external_id');
            $table->string('serial_number')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('firmware')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('provisioning_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('last_contact_at')->nullable();
            $table->json('capabilities')->nullable();
            $table->timestamps();
            $table->unique(['acs_connection_id', 'external_id']);
        });
        Schema::create('device_actions', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignId('customer_device_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('operation');
            $table->string('status')->default('queued');
            $table->string('external_task_id')->nullable();
            $table->text('payload')->nullable();
            $table->string('message')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_actions');
        Schema::dropIfExists('customer_devices');
        Schema::dropIfExists('provisioning_profiles');
        Schema::dropIfExists('acs_connections');
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn('acs_enabled');
        });
    }
};
