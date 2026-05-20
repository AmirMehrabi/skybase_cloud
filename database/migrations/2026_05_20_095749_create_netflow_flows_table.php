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
        Schema::create('netflow_flows', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignId('router_id')->constrained('routers')->cascadeOnDelete();
            $table->string('exporter_ip')->nullable();
            $table->string('source_ip');
            $table->string('destination_ip');
            $table->unsignedSmallInteger('source_port')->nullable();
            $table->unsignedSmallInteger('destination_port')->nullable();
            $table->unsignedSmallInteger('protocol')->nullable();
            $table->unsignedBigInteger('bytes')->default(0);
            $table->unsignedBigInteger('packets')->default(0);
            $table->timestamp('flow_started_at')->nullable();
            $table->timestamp('flow_ended_at')->nullable();
            $table->timestamp('received_at');
            $table->timestamps();

            $table->index(['tenant_id', 'router_id', 'received_at']);
            $table->index(['tenant_id', 'source_ip']);
            $table->index(['tenant_id', 'destination_ip']);
            $table->index(['router_id', 'exporter_ip']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('netflow_flows');
    }
};
