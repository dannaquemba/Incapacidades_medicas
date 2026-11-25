<?php

namespace App\Middleware;

use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class AuthMiddleware
{
    /**
     * Verifica que la petición traiga un token válido y con sesión activa.
     */
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $encabezadoAuth = $request->getHeaderLine('Authorization');
        $token = '';

        if (preg_match('/Bearer\s+(.*)$/i', $encabezadoAuth, $coincidencias)) {
            $token = trim($coincidencias[1]);
        }

        if ($token === '') {
            return $this->responderNoAutorizado('Acceso denegado: token no proporcionado.');
        }

        $usuario = $this->consultarUsuarioPorToken($token);

        if (!$usuario || !$usuario->sesion_activa || $usuario->estado !== 'activo') {
            return $this->responderNoAutorizado('Acceso denegado: token inválido o sesión cerrada.');
        }

        // Guardamos el usuario autenticado en la petición por si se llega a necesitar
        $request = $request->withAttribute('usuario_autenticado', $usuario);

        return $handler->handle($request);
    }

    /**
     * Consulta en modo SOLO LECTURA la tabla usuarios de ms_auth.
     * Está aislada aquí para poder cambiarla fácilmente si es necesario.
     */
    private function consultarUsuarioPorToken(string $token): ?object
    {
        return Capsule::connection('auth_db')
            ->table('usuarios')
            ->select('id', 'nombre', 'correo', 'usuario', 'rol', 'sesion_activa', 'estado')
            ->where('token', $token)
            ->first();
    }

    /**
     * Devuelve respuesta HTTP 401 con el formato estándar JSON.
     */
    private function responderNoAutorizado(string $mensaje): Response
    {
        $response = new SlimResponse();
        $contenido = json_encode([
            'exito'   => false,
            'mensaje' => $mensaje,
            'datos'   => null,
        ], JSON_UNESCAPED_UNICODE);

        $response->getBody()->write($contenido);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401);
    }
}