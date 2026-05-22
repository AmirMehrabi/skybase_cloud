<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radcheck', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->string('username');
            $table->string('attribute');
            $table->string('op', 2)->default('==');
            $table->text('value');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'username', 'attribute']);
            $table->index(['username', 'attribute']);
        });

        Schema::create('radreply', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->string('username');
            $table->string('attribute');
            $table->string('op', 2)->default('=');
            $table->text('value');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'username', 'attribute']);
            $table->index(['username', 'attribute']);
        });

        Schema::create('radusergroup', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->string('username');
            $table->string('groupname');
            $table->integer('priority')->default(1);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'username', 'groupname']);
            $table->index(['username', 'priority']);
        });

        Schema::create('radgroupreply', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->string('groupname');
            $table->string('attribute');
            $table->string('op', 2)->default('=');
            $table->text('value');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'groupname', 'attribute']);
            $table->index(['groupname', 'attribute']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radgroupreply');
        Schema::dropIfExists('radusergroup');
        Schema::dropIfExists('radreply');
        Schema::dropIfExists('radcheck');
    }
};
