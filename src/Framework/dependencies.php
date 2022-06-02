<?php

declare(strict_types=1);

return function () {

    $container = new \League\Container\Container;

    $container->addServiceProvider(new \App\Services\ContainerService);

    $container->delegate(
        new \League\Container\ReflectionContainer
    );

    return $container;

};
