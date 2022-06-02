<?php

declare(strict_types=1);

namespace App\UseCases;

use App\Controllers\AppControllerAbstract;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class StaticPageUseCase extends AppControllerAbstract
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
        $uriPath = $serverRequest->getUri()->getPath();

        // ------------------------------------------------

        if ($uriPath == '/')
            $template = 'Home';
        else {
            $template = \trim($uriPath, '/');
            $template = \str_replace('-', ' ', $template);
            $template = \ucwords($template);
            $template = \str_replace(' ', '', $template);
        }

        // ------------------------------------------------

        $page['/about'] = 'About | ' . $this->cfg->getSiteName();

        $this->data['metaTitle'] = $page[$uriPath];

        // ------------------------------------------------

        return $this->getView([], $template);
    }

}