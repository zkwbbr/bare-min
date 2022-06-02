<?php

declare(strict_types=1);

namespace App\Config;

use MetaRush\DataMapper\DataMapper;

class Cfg extends App
{
    public function __construct(DataMapper $dataMapper, array $cfgData)
    {
        parent::__construct($cfgData);

        $this->dataMapper = $dataMapper;
    }

}