<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Estudiante extends Model
{
    protected $fillable = [
        'carne',
        'nombre',
        'apellidos',
        'consejero_id',
    ];

    use HasFactory;

    public function consejero(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'consejero_id');
    }

    public function grupos(): BelongsToMany
    {
        return $this->belongsToMany(Grupo::class, 'estudiante_matriculados')
            ->using(EstudianteMatriculado::class)
            ->withPivot('nota');
    }
}
