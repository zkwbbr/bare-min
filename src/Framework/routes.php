<?php

use App\UseCases;

// ------------------------------------------------
// Controllers
// ------------------------------------------------

$router->get('/', UseCases\HomeUseCase::class);

// ------------------------------------------------
// Crons
// ------------------------------------------------