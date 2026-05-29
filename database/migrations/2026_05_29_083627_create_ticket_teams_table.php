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
        Schema::create('ticket_teams', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->enum('assignment_strategy', ['random', 'default_agent', 'queue'])->default('queue');
            $table->foreignId('default_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('first_response_minutes')->default(240);
            $table->unsignedInteger('resolution_minutes')->default(2880);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'status', 'sort_order']);
        });

        Schema::create('ticket_team_user', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignId('ticket_team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('accepts_auto_assignment')->default(true);
            $table->timestamps();

            $table->unique(['ticket_team_id', 'user_id']);
            $table->index(['tenant_id', 'user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_team_user');
        Schema::dropIfExists('ticket_teams');
    }
};
