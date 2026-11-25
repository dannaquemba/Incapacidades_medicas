<?php

use App\Controllers\EmpleadosController;
use App\Middleware\AuthMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    // Todas las rutas de empleados requieren autenticación
    $app->group('/empleados', function (RouteCollectorProxy $grupo) {
        $grupo->get('', [EmpleadosController::class, 'listarEmpleados']);
        $grupo->get('/{id}', [EmpleadosController::class, 'obtenerEmpleado']);
        $grupo->post('', [EmpleadosController::class, 'crearEmpleado']);
        $grupo->put('/{id}', [EmpleadosController::class, 'actualizarEmpleado']);
        $grupo->patch('/{id}/estado', [EmpleadosController::class, 'cambiarEstado']);
    })->add(new AuthMiddleware());
};