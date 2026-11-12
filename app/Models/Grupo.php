<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use InvalidArgumentException;

class Grupo extends Model
{
    protected $fillable = [
        'curso_id',
        'numero_ciclo',
        'ano',
        'numero_grupo',
        'abierto',
    ];

    protected function casts(): array
    {
        return [
            'abierto' => 'boolean',
        ];
    }

    use HasFactory;

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function docentes(): BelongsToMany
    {
        return $this->belongsToMany(Docente::class, 'grupo_docente');
    }

    public function estudiantes(): BelongsToMany
    {
        return $this->belongsToMany(Estudiante::class, 'estudiante_matriculados')
            ->using(EstudianteMatriculado::class)
            ->withPivot('nota');
    }

    public function asignarDocente(Docente $docente): self
    {
        if ($this->docentes()->whereKey($docente->getKey())->exists()) {
            return $this;
        }

        if ($this->docentes()->count() >= 2) {
            throw new DomainException('Un grupo no puede tener más de dos docentes.');
        }

        $this->docentes()->attach($docente);

        return $this;
    }

    public function puedeAbrirse(): bool
    {
        return $this->docentes()->exists()
            && $this->estudiantes()->count() >= 15;
    }

    public function abrir(): self
    {
        if (! $this->puedeAbrirse()) {
            throw new DomainException('El grupo requiere un docente y al menos quince estudiantes.');
        }

        $this->forceFill(['abierto' => true])->save();

        return $this;
    }

    public function registrarNota(Estudiante $estudiante, float $nota): void
    {
        if ($nota < 0 || $nota > 10) {
            throw new InvalidArgumentException('La nota debe estar entre 0 y 10.');
        }

        if (! $this->estudiantes()->whereKey($estudiante->getKey())->exists()) {
            throw new DomainException('El estudiante no está matriculado en este grupo.');
        }

        $this->estudiantes()->updateExistingPivot($estudiante->getKey(), ['nota' => $nota]);
    }
}
