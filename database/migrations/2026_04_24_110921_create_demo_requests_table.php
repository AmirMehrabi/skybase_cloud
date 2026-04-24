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
        Schema::create('demo_requests', function (Blueprint $table) {
            $table->id();
            $table->string('requested_plan');
            $table->string('business_name');
            $table->string('contact_name');
            $table->string('email');
            $table->string('phone', 30)->nullable();
            $table->string('country');
            $table->string('company_website')->nullable();
            $table->unsignedInteger('customer_count');
            $table->string('current_system')->nullable();
            $table->string('deployment_timeline')->nullable();
            $table->text('message')->nullable();
            $table->string('source_page')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demo_requests');
    }
};
