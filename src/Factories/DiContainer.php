<?php

declare(strict_types=1);

namespace App\Factories;

class DiContainer
{
    public static function getInstance(): \League\Container\Container
    {
        $diContainer = require __DIR__ . '/../Framework/dependencies.php';

        return \call_user_func($diContainer);
    }

}