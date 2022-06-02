<?php

declare(strict_types=1);

namespace App\UseCases;

use App\Controllers\AppControllerAbstract;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class HomeUseCase extends AppControllerAbstract
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     *
     * @param ServerRequestInterface $serverRequest
     * @param mixed[] $args
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $serverRequest, array $args): ResponseInterface
    {
        return $this->getView();
    }

}