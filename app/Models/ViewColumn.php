<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViewColumn extends Model
{
    use HasFactory;

    protected $table = 'view_column';
    
    protected $fillable = [
        'id_view',
        'name',
        'type',
        'is_nullable',
        'description',
        'max_lengh',
        'precision',
        'scale',
    ];

    // Pas de timestamps automatiques pour cette table
    public $timestamps = true;

    public function viewDescription()
    {
        return $this->belongsTo(ViewDescription::class, 'id_view');
    }
}