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
        Schema::create('work_order_materials', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->string('direction');
            $table->string('sku')->nullable();
            $table->string('description');
            $table->string('serial_number')->nullable();
            $table->string('mac_address')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'work_order_id']);
            $table->index(['tenant_id', 'serial_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_order_materials');
    }
};
