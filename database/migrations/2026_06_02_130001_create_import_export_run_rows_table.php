<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_export_run_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_export_run_id')->constrained('import_export_runs')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->string('status');
            $table->string('identifier')->nullable();
            $table->string('action')->nullable();
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['import_export_run_id', 'status']);
            $table->index(['import_export_run_id', 'row_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_export_run_rows');
    }
};
