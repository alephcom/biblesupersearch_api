<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpAccessLog extends Model 
{
    protected $table = 'ip_access_log';
    
    protected $fillable = ['ip_id', 'date', 'count', 'limit_reached'];

    public function ipAccess()
    {
        return $this->belongsTo(IpAccess::class, 'ip_id');
    }
}
