<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\App;

return function (App $app) {
    // Responde a las peticiones preflight (OPTIONS) del navegador
    $app->options('/{routes:.+}', function (Request $request, Response $response) {
        return $response;
    });

    // Agrega encabezados CORS a todas las respuestas
    $app->add(function (Request $request, RequestHandler $handler): Response {
        $response = $handler->handle($request);

        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
    });
};