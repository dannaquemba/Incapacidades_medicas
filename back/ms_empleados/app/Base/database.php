<?php

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule();

// 1. Conexión principal (propia de ms_empleados)
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => '127.0.0.1',
    'port'      => '3306',
    'database'  => 'ms_empleados',
    'username'  => 'root',
    'password'  => '',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
]);

// 2. Segunda conexión (SOLO LECTURA hacia ms_auth para validar tokens en AuthMiddleware)
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => '127.0.0.1',
    'port'      => '3306',
    'database'  => 'ms_auth',
    'username'  => 'root',
    'password'  => '',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
], 'auth_db');

$capsule->setAsGlobal();
$capsule->bootEloquent();