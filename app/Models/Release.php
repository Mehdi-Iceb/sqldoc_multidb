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
        'project_id',
        'version_number',
        'description',
        'created_at', 
        'updated_at'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    public function tableStructures()
    {
        return $this->hasMany(TableStructure::class, 'release_id', 'id');
    }
}