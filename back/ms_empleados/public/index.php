<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;

// 1. Conectar Eloquent (ms_empleados y auth_db)
require __DIR__ . '/../app/Base/database.php';

// 2. Crear la aplicación Slim
$app = AppFactory::create();

// 3. Permitir lectura automática de cuerpos JSON
$app->addBodyParsingMiddleware();

// 4. Registrar rutas
$registrarRutas = require __DIR__ . '/../app/Presentation/Routers/endpoints.php';
$registrarRutas($app);

// 5. Enrutamiento y manejo de errores
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

// 6. Habilitar CORS al final para que envuelva incluso errores 401/404/500
$configurarCors = require __DIR__ . '/../app/Base/cors.php';
$configurarCors($app);

// 7. Iniciar el microservicio
$app->run();