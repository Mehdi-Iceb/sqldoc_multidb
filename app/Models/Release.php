<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Release extends Model
{

    use HasFactory;

    protected $dateFormat = 'd-m-Y H:i:s';
   
    protected $table = 'release';

    protected $fillable = [
        'id_db',
        'version_number',
        'created_at', 
        'updated_at'
    ];

    public function dbdescription(): BelongsTo
    {
        return $this->belongsTo(DbDescription::class, 'id_db', 'id');
    }
}