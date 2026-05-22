<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createRadCheckTable();
        $this->createRadReplyTable();
        $this->createRadGroupCheckTable();
        $this->createRadGroupReplyTable();
        $this->createRadUserGroupTable();
        $this->createRadPostAuthTable();
        $this->createRadAcctTable();
        $this->createNasTable();
        $this->createRadIpPoolTable();
    }

    public function down(): void
    {
        foreach ([
            'radippool',
            'nas',
            'radacct',
            'radpostauth',
            'radusergroup',
            'radgroupreply',
            'radgroupcheck',
            'radreply',
            'radcheck',
        ] as $table) {
            if (Schema::hasTable($table)) {
                Schema::dropIfExists($table);
            }
        }
    }

    private function createRadCheckTable(): void
    {
        if (Schema::hasTable('radcheck')) {
            return;
        }

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
    }

    private function createRadReplyTable(): void
    {
        if (Schema::hasTable('radreply')) {
            return;
        }

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
    }

    private function createRadGroupCheckTable(): void
    {
        if (Schema::hasTable('radgroupcheck')) {
            return;
        }

        Schema::create('radgroupcheck', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->string('groupname');
            $table->string('attribute');
            $table->string('op', 2)->default('==');
            $table->text('value');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'groupname', 'attribute']);
            $table->index(['groupname', 'attribute']);
        });
    }

    private function createRadGroupReplyTable(): void
    {
        if (Schema::hasTable('radgroupreply')) {
            return;
        }

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

    private function createRadUserGroupTable(): void
    {
        if (Schema::hasTable('radusergroup')) {
            return;
        }

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
    }

    private function createRadPostAuthTable(): void
    {
        if (Schema::hasTable('radpostauth')) {
            return;
        }

        Schema::create('radpostauth', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->string('username')->nullable();
            $table->string('pass')->nullable();
            $table->text('reply')->nullable();
            $table->timestamp('authdate')->useCurrent();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['tenant_id', 'username']);
            $table->index(['authdate']);
        });
    }

    private function createRadAcctTable(): void
    {
        if (Schema::hasTable('radacct')) {
            return;
        }

        Schema::create('radacct', function (Blueprint $table): void {
            $table->bigIncrements('radacctid');
            $table->string('tenant_id');
            $table->string('acctsessionid', 64);
            $table->string('acctuniqueid', 32)->nullable()->unique();
            $table->string('username', 64)->nullable()->index();
            $table->string('groupname', 64)->nullable();
            $table->string('realm', 64)->nullable();
            $table->string('nasipaddress', 15)->nullable()->index();
            $table->string('nasportid', 15)->nullable();
            $table->string('nasporttype', 32)->nullable();
            $table->dateTime('acctstarttime')->nullable()->index();
            $table->dateTime('acctupdatetime')->nullable()->index();
            $table->dateTime('acctstoptime')->nullable()->index();
            $table->unsignedInteger('acctinterval')->nullable();
            $table->unsignedInteger('acctsessiontime')->nullable();
            $table->string('acctauthentic', 32)->nullable();
            $table->string('connectinfo_start', 50)->nullable();
            $table->string('connectinfo_stop', 50)->nullable();
            $table->unsignedBigInteger('acctinputoctets')->nullable();
            $table->unsignedBigInteger('acctoutputoctets')->nullable();
            $table->string('calledstationid', 50)->nullable();
            $table->string('callingstationid', 50)->nullable();
            $table->string('acctterminatecause', 32)->nullable();
            $table->string('servicetype', 32)->nullable();
            $table->string('framedprotocol', 32)->nullable();
            $table->string('framedipaddress', 15)->nullable();
            $table->string('framedipv6address', 45)->nullable();
            $table->string('framedipv6prefix', 45)->nullable();
            $table->string('framedinterfaceid', 44)->nullable();
            $table->string('delegatedipv6prefix', 45)->nullable();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['tenant_id', 'username', 'acctstoptime'], 'radacct_tenant_username_stop_index');
            $table->index(['tenant_id', 'acctsessionid'], 'radacct_tenant_session_index');
        });
    }

    private function createNasTable(): void
    {
        if (Schema::hasTable('nas')) {
            return;
        }

        Schema::create('nas', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->string('shortname');
            $table->string('nasname');
            $table->string('type')->default('other');
            $table->integer('ports')->nullable();
            $table->string('secret')->nullable();
            $table->string('server')->nullable();
            $table->string('community')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'nasname']);
            $table->index(['tenant_id', 'shortname']);
        });
    }

    private function createRadIpPoolTable(): void
    {
        if (Schema::hasTable('radippool')) {
            return;
        }

        Schema::create('radippool', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->string('pool_name');
            $table->string('framedipaddress', 15);
            $table->string('nasipaddress', 15)->nullable();
            $table->string('calledstationid', 50)->nullable();
            $table->string('callingstationid', 50)->nullable();
            $table->timestamp('expiry_time')->nullable();
            $table->string('username')->nullable();
            $table->string('pool_key')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'framedipaddress']);
            $table->index(['tenant_id', 'pool_name']);
        });
    }
};
