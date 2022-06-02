<?php

declare(strict_types=1);

namespace App\Services;

class FactoryService
{
    /**
     * @template T of object
     * @param class-string<T> $classFqn
     * @return T
     */
    public function getInstance(string $classFqn): object
    {
        $diContainer = \App\Factories\DiContainer::getInstance();

        /** @var T $object */
        $object = $diContainer->get($classFqn);

        return $object;
    }

}