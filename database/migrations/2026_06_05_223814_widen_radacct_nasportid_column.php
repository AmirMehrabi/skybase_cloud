<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('radacct', function (Blueprint $table) {
            DB::statement("
                ALTER TABLE `radacct`
                MODIFY `nasportid` VARCHAR(255) NULL DEFAULT NULL
            ");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('radacct', function (Blueprint $table) {
            DB::statement("
                ALTER TABLE `radacct`
                MODIFY `nasportid` VARCHAR(32) NULL DEFAULT NULL
            ");
        });
    }
};
