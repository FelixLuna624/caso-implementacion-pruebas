<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Docente extends Model
{
    protected $fillable = [
        'cod_empleado',
        'nombre',
        'apellidos',
    ];

    use HasFactory;

    public function grupos(): BelongsToMany
    {
        return $this->belongsToMany(Grupo::class, 'grupo_docente');
    }

    public function estudiantes(): HasMany
    {
        return $this->hasMany(Estudiante::class, 'consejero_id');
    }
}
