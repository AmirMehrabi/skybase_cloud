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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('internal_name')->unique();
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->string('visibility')->default('public');
            $table->string('type');
            $table->string('category')->nullable();
            $table->unsignedInteger('download_speed')->default(0);
            $table->unsignedInteger('upload_speed')->default(0);
            $table->unsignedInteger('burst_download')->default(0);
            $table->unsignedInteger('burst_upload')->default(0);
            $table->string('bandwidth_unit')->default('Mbps');
            $table->unsignedInteger('data_limit')->nullable();
            $table->string('data_unit')->default('GB');
            $table->boolean('unlimited')->default(false);
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->string('billing_cycle')->default('monthly');
            $table->decimal('setup_fee', 10, 2)->default(0);
            $table->string('tax_profile')->nullable();
            $table->string('router_profile')->nullable();
            $table->string('ip_pool')->nullable();
            $table->unsignedTinyInteger('priority')->default(5);
            $table->boolean('contract_required')->default(false);
            $table->unsignedInteger('contract_duration')->nullable();
            $table->date('available_from')->nullable();
            $table->date('available_to')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
