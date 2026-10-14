<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->string('pppoe_password_hash')->nullable();
        });

        Schema::table('routers', function (Blueprint $table): void {
            $table->string('api_password_hash')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropColumn('pppoe_password_hash');
        });

        Schema::table('routers', function (Blueprint $table): void {
            $table->dropColumn('api_password_hash');
        });
    }
};
