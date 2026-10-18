<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ipdr_bindings', function (Blueprint $table): void {
            $table->id();
            $table->uuid('assignment_id')->unique();
            $table->string('tenant_id');
            $table->unsignedBigInteger('subscription_id');
            $table->string('binding_key')->nullable()->unique();
            $table->string('subscription_code');
            $table->string('customer_label')->nullable();
            $table->string('router_id', 64);
            $table->string('pool_id', 64)->nullable();
            $table->string('address', 45);
            $table->unsignedSmallInteger('prefix_length');
            $table->string('origin', 32);
            $table->dateTime('valid_from');
            $table->dateTime('valid_until')->nullable();
            $table->unsignedBigInteger('revision')->default(1);
            $table->timestamps();
            $table->index(['tenant_id', 'subscription_id', 'valid_until'], 'ipdr_binding_sub_time');
        });
        Schema::create('ipdr_outboxes', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->index();
            $table->json('payload');
            $table->timestamp('delivered_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipdr_outboxes');
        Schema::dropIfExists('ipdr_bindings');
    }
};
