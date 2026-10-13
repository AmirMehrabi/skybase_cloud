<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['plans', 'subscriptions', 'invoices'] as $name) {
            if (! Schema::hasIndex($name, ['tenant_id', 'id'], 'unique')) {
                Schema::table($name, fn (Blueprint $table) => $table->unique(['tenant_id', 'id']));
            }
        }
    }

    public function down(): void
    {
        foreach (['plans', 'subscriptions', 'invoices'] as $name) {
            if (Schema::hasIndex($name, ['tenant_id', 'id'], 'unique')) {
                Schema::table($name, fn (Blueprint $table) => $table->dropUnique(['tenant_id', 'id']));
            }
        }
    }
};
