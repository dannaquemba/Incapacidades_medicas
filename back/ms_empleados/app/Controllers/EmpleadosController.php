<?php

namespace App\Controllers;

use App\Presentation\Repositories\EmpleadosRepository;
use DateTime;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class EmpleadosController
{
    private EmpleadosRepository $repositorio;

    private const ESTADOS_VALIDOS = ['activo', 'inactivo'];

    public function __construct()
    {
        $this->repositorio = new EmpleadosRepository();
    }

    /**
     * GET /empleados
     * Filtros opcionales por query: ?documento=...&area=...&estado=...
     */
    public function listarEmpleados(Request $request, Response $response): Response
    {
        try {
            $filtros = $request->getQueryParams();
            $empleados = $this->repositorio->listarConFiltros($filtros);

            return $this->responderJson(
                $response,
                true,
                'Listado de empleados obtenido correctamente.',
                $empleados,
                200
            );
        } catch (\Throwable $e) {
            return $this->responderJson(
                $response,
                false,
                'Error al listar empleados: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * GET /empleados/{id}
     */
    public function obtenerEmpleado(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) ($args['id'] ?? 0);
            $empleado = $this->repositorio->buscarPorId($id);

            if (!$empleado) {
                return $this->responderJson(
                    $response,
                    false,
                    'Empleado no encontrado.',
                    null,
                    404
                );
            }

            return $this->responderJson(
                $response,
                true,
                'Empleado encontrado.',
                $empleado,
                200
            );
        } catch (\Throwable $e) {
            return $this->responderJson(
                $response,
                false,
                'Error al consultar empleado: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * POST /empleados
     */
    public function crearEmpleado(Request $request, Response $response): Response
    {
        try {
            $cuerpo = $this->obtenerDatosCuerpo($request);

            // Validar campos obligatorios
            $errorObligatorios = $this->validarCamposObligatorios($cuerpo);
            if ($errorObligatorios !== null) {
                return $this->responderJson($response, false, $errorObligatorios, null, 400);
            }

            $datos = $this->limpiarDatosEmpleado($cuerpo);

            // Validar formato de correo, fecha y estado
            $errorFormato = $this->validarReglasNegocio($datos);
            if ($errorFormato !== null) {
                return $this->responderJson($response, false, $errorFormato, null, 422);
            }

            // Validar que documento y correo no existan
            if ($this->repositorio->existeDocumento($datos['documento'])) {
                return $this->responderJson(
                    $response,
                    false,
                    'Ya existe un empleado registrado con ese documento.',
                    null,
                    422
                );
            }

            if ($this->repositorio->existeCorreo($datos['correo'])) {
                return $this->responderJson(
                    $response,
                    false,
                    'Ya existe un empleado registrado con ese correo.',
                    null,
                    422
                );
            }

            $empleado = $this->repositorio->crear($datos);

            return $this->responderJson(
                $response,
                true,
                'Empleado creado correctamente.',
                $empleado,
                201
            );
        } catch (\Throwable $e) {
            return $this->responderJson(
                $response,
                false,
                'Error al crear empleado: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * PUT /empleados/{id}
     */
    public function actualizarEmpleado(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) ($args['id'] ?? 0);
            $empleado = $this->repositorio->buscarPorId($id);

            if (!$empleado) {
                return $this->responderJson($response, false, 'Empleado no encontrado.', null, 404);
            }

            $cuerpo = $this->obtenerDatosCuerpo($request);

            $errorObligatorios = $this->validarCamposObligatorios($cuerpo);
            if ($errorObligatorios !== null) {
                return $this->responderJson($response, false, $errorObligatorios, null, 400);
            }

            // Si no se envía estado en la edición, conserva el que ya tenía
            if (empty($cuerpo['estado'])) {
                $cuerpo['estado'] = $empleado->estado;
            }

            $datos = $this->limpiarDatosEmpleado($cuerpo);

            $errorFormato = $this->validarReglasNegocio($datos);
            if ($errorFormato !== null) {
                return $this->responderJson($response, false, $errorFormato, null, 422);
            }

            // Validar duplicados excluyendo al empleado actual
            if ($this->repositorio->existeDocumento($datos['documento'], $id)) {
                return $this->responderJson(
                    $response,
                    false,
                    'Ya existe otro empleado con ese documento.',
                    null,
                    422
                );
            }

            if ($this->repositorio->existeCorreo($datos['correo'], $id)) {
                return $this->responderJson(
                    $response,
                    false,
                    'Ya existe otro empleado con ese correo.',
                    null,
                    422
                );
            }

            $empleadoActualizado = $this->repositorio->actualizar($empleado, $datos);

            return $this->responderJson(
                $response,
                true,
                'Empleado actualizado correctamente.',
                $empleadoActualizado,
                200
            );
        } catch (\Throwable $e) {
            return $this->responderJson(
                $response,
                false,
                'Error al actualizar empleado: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * PATCH /empleados/{id}/estado
     */
    public function cambiarEstado(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) ($args['id'] ?? 0);
            $empleado = $this->repositorio->buscarPorId($id);

            if (!$empleado) {
                return $this->responderJson($response, false, 'Empleado no encontrado.', null, 404);
            }

            $cuerpo = $this->obtenerDatosCuerpo($request);
            $nuevoEstado = trim((string) ($cuerpo['estado'] ?? ''));

            if ($nuevoEstado === '') {
                return $this->responderJson(
                    $response,
                    false,
                    'Debes indicar el nuevo estado (activo o inactivo).',
                    null,
                    400
                );
            }

            if (!in_array($nuevoEstado, self::ESTADOS_VALIDOS, true)) {
                return $this->responderJson(
                    $response,
                    false,
                    'Estado no válido. Solo se permite: activo o inactivo.',
                    null,
                    422
                );
            }

            $empleadoActualizado = $this->repositorio->cambiarEstado($empleado, $nuevoEstado);

            return $this->responderJson(
                $response,
                true,
                'Estado del empleado actualizado correctamente.',
                $empleadoActualizado,
                200
            );
        } catch (\Throwable $e) {
            return $this->responderJson(
                $response,
                false,
                'Error al cambiar estado del empleado: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * Valida que los campos obligatorios vengan llenos.
     */
    private function validarCamposObligatorios(array $cuerpo): ?string
    {
        $requeridos = [
            'nombres',
            'apellidos',
            'documento',
            'correo',
            'telefono',
            'cargo',
            'area',
            'fecha_ingreso',
        ];

        $faltantes = [];
        foreach ($requeridos as $campo) {
            if (!isset($cuerpo[$campo]) || trim((string) $cuerpo[$campo]) === '') {
                $faltantes[] = $campo;
            }
        }

        if (!empty($faltantes)) {
            return 'Faltan campos obligatorios: ' . implode(', ', $faltantes) . '.';
        }

        return null;
    }

    /**
     * Limpia espacios de los campos y asigna 'activo' como estado por defecto.
     */
    private function limpiarDatosEmpleado(array $cuerpo): array
    {
        return [
            'nombres'       => trim((string) $cuerpo['nombres']),
            'apellidos'     => trim((string) $cuerpo['apellidos']),
            'documento'     => trim((string) $cuerpo['documento']),
            'correo'        => trim((string) $cuerpo['correo']),
            'telefono'      => trim((string) $cuerpo['telefono']),
            'cargo'         => trim((string) $cuerpo['cargo']),
            'area'          => trim((string) $cuerpo['area']),
            'fecha_ingreso' => trim((string) $cuerpo['fecha_ingreso']),
            'estado'        => trim((string) ($cuerpo['estado'] ?? 'activo')),
        ];
    }

    /**
     * Valida correo electrónico, formato de fecha (YYYY-MM-DD) y estado permitido.
     */
    private function validarReglasNegocio(array $datos): ?string
    {
        if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            return 'El correo electrónico no tiene un formato válido.';
        }

        if (!$this->esFechaValida($datos['fecha_ingreso'])) {
            return 'La fecha de ingreso no es válida. Usa el formato YYYY-MM-DD.';
        }

        if (!in_array($datos['estado'], self::ESTADOS_VALIDOS, true)) {
            return 'Estado no válido. Solo se permite: activo o inactivo.';
        }

        return null;
    }

    /**
     * Comprueba que la fecha exista y tenga el formato YYYY-MM-DD.
     */
    private function esFechaValida(string $fecha): bool
    {
        $objetoFecha = DateTime::createFromFormat('Y-m-d', $fecha);
        return $objetoFecha !== false && $objetoFecha->format('Y-m-d') === $fecha;
    }

    private function obtenerDatosCuerpo(Request $request): array
    {
        $datos = $request->getParsedBody();
        if (is_array($datos)) {
            return $datos;
        }

        $json = json_decode((string) $request->getBody(), true);
        return is_array($json) ? $json : [];
    }

    private function responderJson(
        Response $response,
        bool $exito,
        string $mensaje,
        mixed $datos = null,
        int $codigoHttp = 200
    ): Response {
        $contenido = json_encode([
            'exito'   => $exito,
            'mensaje' => $mensaje,
            'datos'   => $datos,
        ], JSON_UNESCAPED_UNICODE);

        $response->getBody()->write($contenido);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($codigoHttp);
    }
}