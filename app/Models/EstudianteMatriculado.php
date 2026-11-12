<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EstudianteMatriculado extends Pivot
{
    protected $table = 'estudiante_matriculados';

    public $incrementing = true;

    protected $fillable = [
        'grupo_id',
        'estudiante_id',
        'nota',
    ];

    protected function casts(): array
    {
        return [
            'nota' => 'float',
        ];
    }

    use HasFactory;

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }
}
