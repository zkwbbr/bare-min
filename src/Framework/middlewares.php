<?php

/* @var $router \League\Route\Router */

// ------------------------------------------------
// Middlewares
// ------------------------------------------------

if (\file_exists(APP_ROOT_DIR . '.maintenance_mode'))
    $router->middleware(new \Middlewares\Shutdown);

// ------------------------------------------------

$router->middleware(new \App\Middlewares\AntiXssMiddleware);

// ------------------------------------------------
