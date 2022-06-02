<?php

declare(strict_types=1);

namespace App\Controllers;

abstract class AppControllerAbstract extends BaseControllerAbstract
{
    public function __construct()
    {
        parent::__construct();

        // ------------------------------------------------
        // global template vars
        // ------------------------------------------------
        $this->data['metaTitle'] = $this->cfg->getAppName();
    }

}