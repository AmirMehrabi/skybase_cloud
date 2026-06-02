<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->tables() as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('tenant_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables()) as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('tenant_id')->nullable(false)->change();
            });
        }
    }

    /**
     * @return array<int, string>
     */
    private function tables(): array
    {
        return [
            'radcheck',
            'radreply',
            'radgroupcheck',
            'radgroupreply',
            'radusergroup',
            'radpostauth',
            'nas',
            'radippool',
        ];
    }
};
