<?php

declare(strict_types=1);

namespace App\Factories;

use App\Config\Cfg;

class AppCfg
{
    public static function getInstance(): Cfg
    {
        $diContainer = DiContainer::getInstance();

        /** @var Cfg $appCfg */
        $appCfg = $diContainer->get(Cfg::class);

        return $appCfg;
    }

}