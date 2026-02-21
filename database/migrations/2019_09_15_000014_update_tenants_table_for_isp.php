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
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('name')->after('id');
            $table->string('slug')->unique()->after('name');
            $table->string('company_name')->after('slug');
            $table->string('email')->after('company_name');
            $table->string('phone')->nullable()->after('email');
            $table->string('country')->nullable()->after('phone');
            $table->string('timezone')->default('UTC')->after('country');
            $table->enum('status', ['pending', 'active', 'suspended'])->default('pending')->after('timezone');
            $table->foreignId('plan_id')->nullable()->after('status')->constrained('plans')->nullOnDelete();
            $table->timestamp('trial_ends_at')->nullable()->after('plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn([
                'name',
                'slug',
                'company_name',
                'email',
                'phone',
                'country',
                'timezone',
                'status',
                'plan_id',
                'trial_ends_at',
            ]);
        });
    }
};
