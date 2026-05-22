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
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('ldap_guid')->nullable()->after('default_tax_percentage');
            $table->string('ldap_domain')->nullable()->after('ldap_guid');
            $table->text('ldap_dn')->nullable()->after('ldap_domain');
            $table->timestamp('ldap_synced_at')->nullable()->after('ldap_dn');

            $table->index(['tenant_id', 'ldap_guid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'ldap_guid']);
            $table->dropColumn(['ldap_guid', 'ldap_domain', 'ldap_dn', 'ldap_synced_at']);
        });
    }
};
