<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RadiusAccountingRecord extends Model
{
    protected $table = 'radacct';

    public $timestamps = false;

    protected $primaryKey = 'radacctid';

    protected $fillable = [
        'tenant_id',
        'acctsessionid',
        'acctuniqueid',
        'username',
        'groupname',
        'realm',
        'nasipaddress',
        'nasportid',
        'nasporttype',
        'acctstarttime',
        'acctupdatetime',
        'acctstoptime',
        'acctinterval',
        'acctsessiontime',
        'acctauthentic',
        'connectinfo_start',
        'connectinfo_stop',
        'acctinputoctets',
        'acctoutputoctets',
        'calledstationid',
        'callingstationid',
        'acctterminatecause',
        'servicetype',
        'framedprotocol',
        'framedipaddress',
        'framedipv6address',
        'framedipv6prefix',
        'framedinterfaceid',
        'delegatedipv6prefix',
    ];

    protected function casts(): array
    {
        return [
            'acctstarttime' => 'datetime',
            'acctupdatetime' => 'datetime',
            'acctstoptime' => 'datetime',
            'acctsessiontime' => 'integer',
            'acctinterval' => 'integer',
            'acctinputoctets' => 'integer',
            'acctoutputoctets' => 'integer',
        ];
    }

    public function scopeOpenSession(Builder $query): Builder
    {
        return $query->whereNull('acctstoptime');
    }

    public function scopeForUsername(Builder $query, string $username): Builder
    {
        return $query->where('username', $username);
    }
}
