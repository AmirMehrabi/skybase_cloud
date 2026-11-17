<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['users', 'customers'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('government_identity_hash', 64)->nullable();
                $table->unique(['tenant_id', 'government_identity_hash']);
            });
        }
    }

    public function down(): void
    {
        foreach (['users', 'customers'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropUnique(['tenant_id', 'government_identity_hash']);
                $table->dropColumn('government_identity_hash');
            });
        }
    }
};
