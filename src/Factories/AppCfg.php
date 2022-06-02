<?php

declare(strict_types=1);

namespace App\Factories;

class AppCfg
{
    public static function getInstance(): \App\Config\Cfg
    {
        $diContainer = DiContainer::getInstance();

        /** @var T $appCfg */
        $appCfg = $diContainer->get(\App\Config\Cfg::class);

        return $appCfg;
    }

}