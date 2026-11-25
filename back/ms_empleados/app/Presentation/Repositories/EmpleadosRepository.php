<?php

namespace App\Presentation\Repositories;

use App\Models\Empleado;
use Illuminate\Database\Eloquent\Collection;

class EmpleadosRepository
{
    /**
     * Lista empleados aplicando filtros opcionales: documento, area y estado.
     */
    public function listarConFiltros(array $filtros): Collection
    {
        $consulta = Empleado::query();

        if (!empty($filtros['documento'])) {
            $documento = trim((string) $filtros['documento']);
            $consulta->where('documento', 'like', "%{$documento}%");
        }

        if (!empty($filtros['area'])) {
            $area = trim((string) $filtros['area']);
            $consulta->where('area', $area);
        }

        if (!empty($filtros['estado'])) {
            $estado = trim((string) $filtros['estado']);
            $consulta->where('estado', $estado);
        }

        return $consulta->orderBy('id', 'desc')->get();
    }

    /**
     * Busca un empleado por su ID.
     */
    public function buscarPorId(int $id): ?Empleado
    {
        return Empleado::find($id);
    }

    /**
     * Verifica si ya existe un empleado con ese documento (excluyendo un ID si se está editando).
     */
    public function existeDocumento(string $documento, ?int $idExcluir = null): bool
    {
        $consulta = Empleado::where('documento', $documento);

        if ($idExcluir !== null) {
            $consulta->where('id', '!=', $idExcluir);
        }

        return $consulta->exists();
    }

    /**
     * Verifica si ya existe un empleado con ese correo (excluyendo un ID si se está editando).
     */
    public function existeCorreo(string $correo, ?int $idExcluir = null): bool
    {
        $consulta = Empleado::where('correo', $correo);

        if ($idExcluir !== null) {
            $consulta->where('id', '!=', $idExcluir);
        }

        return $consulta->exists();
    }

    /**
     * Crea un nuevo empleado en la base de datos.
     */
    public function crear(array $datos): Empleado
    {
        return Empleado::create($datos);
    }

    /**
     * Actualiza los datos de un empleado existente.
     */
    public function actualizar(Empleado $empleado, array $datos): Empleado
    {
        $empleado->fill($datos);
        $empleado->save();
        return $empleado;
    }

    /**
     * Cambia el estado (activo / inactivo) de un empleado.
     */
    public function cambiarEstado(Empleado $empleado, string $nuevoEstado): Empleado
    {
        $empleado->estado = $nuevoEstado;
        $empleado->save();
        return $empleado;
    }
}