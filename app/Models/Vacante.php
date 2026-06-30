<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vacante extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'integer';

    protected $fillable = [
        'id',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
    ];

    public function cargos()
    {
        return $this->belongsToMany(Cargo::class);
    }
}
