<?php

use App\Models\Curso;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\EstudianteMatriculado;
use App\Models\Grupo;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->curso = Curso::factory()->create([
        'sigla' => 'IF6100',
        'nombre_curso' => 'Análisis y Diseño de Sistemas',
        'creditos' => 4,
    ]);

    $this->grupo = Grupo::factory()->create([
        'curso_id' => $this->curso->id,
        'numero_ciclo' => 2,
        'ano' => 2026,
        'numero_grupo' => 1,
        'abierto' => false,
    ]);

    $this->ana = Docente::factory()->create(['cod_empleado' => 1001, 'nombre' => 'Ana', 'apellidos' => 'Mora Vargas']);
    $this->luis = Docente::factory()->create(['cod_empleado' => 1002, 'nombre' => 'Luis', 'apellidos' => 'Rojas Solano']);
    $this->carla = Docente::factory()->create(['cod_empleado' => 1003, 'nombre' => 'Carla', 'apellidos' => 'Jiménez Araya']);
});

test('CP-07 un grupo sin docente no se abre aunque tenga cupo completo', function () {
    $estudiantes = Estudiante::factory()->count(20)->create(['consejero_id' => $this->ana->id]);
    $this->grupo->estudiantes()->attach($estudiantes->modelKeys());

    expect($this->grupo->puedeAbrirse())->toBeFalse();
    expect(fn () => $this->grupo->abrir())->toThrow(DomainException::class);
    expect($this->grupo->fresh()->abierto)->toBeFalse();
});

test('CP-08 un estudiante queda ligado a su profesor consejero', function () {
    $estudiante = Estudiante::factory()->create([
        'carne' => 'C12345',
        'nombre' => 'María',
        'apellidos' => 'Solís Quesada',
        'consejero_id' => $this->ana->id,
    ]);

    expect($estudiante->consejero->cod_empleado)->toBe(1001);

    $docente = $this->ana->fresh();

    expect($docente->estudiantes)->toHaveCount(1)
        ->and($docente->estudiantes->first()->carne)->toBe('C12345');
    expect($estudiante->fresh()->consejero_id)->toBe($this->ana->id);
});

test('CP-09 cambiar de consejero reemplaza al anterior y no lo suma', function () {
    $estudiante = Estudiante::factory()->create([
        'carne' => 'C12345',
        'consejero_id' => $this->ana->id,
    ]);

    $estudiante->consejero()->associate($this->luis)->save();

    expect($estudiante->fresh()->consejero->cod_empleado)->toBe(1002);
    expect($this->ana->fresh()->estudiantes)->toHaveCount(0);
    expect($this->luis->fresh()->estudiantes)->toHaveCount(1);
    expect($estudiante->fresh()->consejero_id)->toBe($this->luis->id);
});

test('CP-10 al matricular, la nota queda vacía', function () {
    $estudiante = Estudiante::factory()->create([
        'carne' => 'C12345',
        'consejero_id' => $this->ana->id,
    ]);

    $this->grupo->estudiantes()->attach($estudiante->id);

    $pivot = $this->grupo->fresh()->estudiantes->first()->pivot;

    expect($pivot)->toBeInstanceOf(EstudianteMatriculado::class)
        ->and($pivot->nota)->toBeNull();
    expect(EstudianteMatriculado::count())->toBe(1);
    expect(EstudianteMatriculado::query()
        ->where('grupo_id', $this->grupo->id)
        ->where('estudiante_id', $estudiante->id)
        ->exists())->toBeTrue();
});

test('CP-11 registra la nota al finalizar el curso', function () {
    $estudiante = Estudiante::factory()->create([
        'carne' => 'C12345',
        'consejero_id' => $this->ana->id,
    ]);
    $otro = Estudiante::factory()->create(['consejero_id' => $this->ana->id]);

    $this->grupo->estudiantes()->attach([$estudiante->id, $otro->id]);

    $this->grupo->registrarNota($estudiante, 8.5);

    $matricula = EstudianteMatriculado::query()
        ->where('grupo_id', $this->grupo->id)
        ->where('estudiante_id', $estudiante->id)
        ->first();

    expect($matricula->nota)->toBe(8.5)
        ->and($matricula->nota)->toBeFloat();

    $otraMatricula = EstudianteMatriculado::query()
        ->where('grupo_id', $this->grupo->id)
        ->where('estudiante_id', $otro->id)
        ->first();

    expect($otraMatricula->nota)->toBeNull();
    expect(EstudianteMatriculado::count())->toBe(2);
    expect($matricula->grupo_id)->toBe($this->grupo->id)
        ->and($matricula->estudiante_id)->toBe($estudiante->id);
});

test('CP-12 rechaza notas fuera del rango de 0 a 10', function (float $valor) {
    $estudiante = Estudiante::factory()->create([
        'carne' => 'C12345',
        'consejero_id' => $this->ana->id,
    ]);
    $this->grupo->estudiantes()->attach($estudiante->id);

    expect(fn () => $this->grupo->registrarNota($estudiante, $valor))
        ->toThrow(InvalidArgumentException::class);

    expect(EstudianteMatriculado::query()
        ->where('grupo_id', $this->grupo->id)
        ->where('estudiante_id', $estudiante->id)
        ->first()
        ->nota)->toBeNull();
})->with([-1, 10.5]);

test('CP-13 un estudiante no se matricula dos veces en el mismo grupo', function () {
    $estudiante = Estudiante::factory()->create([
        'carne' => 'C12345',
        'consejero_id' => $this->ana->id,
    ]);
    $this->grupo->estudiantes()->attach($estudiante->id);

    expect(fn () => $this->grupo->estudiantes()->attach($estudiante->id))
        ->toThrow(QueryException::class);

    expect(EstudianteMatriculado::query()
        ->where('grupo_id', $this->grupo->id)
        ->where('estudiante_id', $estudiante->id)
        ->count())->toBe(1);
});
