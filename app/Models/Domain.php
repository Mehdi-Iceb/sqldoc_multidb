<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    protected $table = 'domains';
    
    
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'domain',
        'tenant_id',
        
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }
}
