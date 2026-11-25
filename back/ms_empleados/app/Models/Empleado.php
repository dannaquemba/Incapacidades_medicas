<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    // Nombre de la tabla en MySQL
    protected $table = 'empleados';

    // Usa created_at y updated_at automáticamente
    public $timestamps = true;

    // Columnas permitidas para asignación masiva
    protected $fillable = [
        'nombres',
        'apellidos',
        'documento',
        'correo',
        'telefono',
        'cargo',
        'area',
        'fecha_ingreso',
        'estado',
    ];
}