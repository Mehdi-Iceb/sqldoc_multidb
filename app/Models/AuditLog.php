<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{

    use HasFactory;
    
    protected $dateFormat = 'd-m-Y H:i:s';
   
    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'db_id',
        'table_id',
        'column_name',
        'change_type',
        'old_data',
        'new_data'
    ];
}