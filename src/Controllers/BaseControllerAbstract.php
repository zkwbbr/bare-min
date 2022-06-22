<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Cfg;
use App\Services\FactoryService;
use App\Factories\AppCfg;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zkwbbr\Utils\PathSegment;
use Zkwbbr\Utils\Redirect;
use Zkwbbr\View\View;
use Laminas\Diactoros\Response;

abstract class BaseControllerAbstract
{
    protected Cfg $cfg;
    protected FactoryService $factoryService;
    protected ResponseInterface $serverResponse;

    /**
     * Template vars
     *
     * @var mixed[]
     */
    protected array $data = [];

    protected function __construct()
    {
        $this->factoryService = new FactoryService;

        $this->cfg = AppCfg::getInstance();

        $this->serverResponse = new Response;
    }

    private function getHttpResponse(string $data, int $status = 200): ResponseInterface
    {
        $this->serverResponse->getBody()->write($data);

        return $this->serverResponse->withHeader('content-type', 'text/html')->withStatus($status);
    }

    /**
     *
     * @param array $data
     * @param string|null $view
     * @param bool $useLayout
     * @param string|null $layoutFile
     * @param int|null $status
     * @return ResponseInterface
     */
    protected function getView(array $data = [], ?string $view = null, bool $useLayout = true, ?string $layoutFile = null, ?int $status = 200): ResponseInterface
    {
        $view ??= $this->getAutoDetectedView();

        // include global data vars
        $data = $data + $this->data;

        // capitalize template path
        $viewsPath = $this->cfg->getViewsPath();
        $viewsPath = \str_replace('/', ' ', $viewsPath);
        $viewsPath = \ucwords($viewsPath);
        $viewsPath = \str_replace(' ', '/', $viewsPath);

        // determine layout file
        $layoutFile ??= 'defaultLayout';

        $view = (new View)
            ->setData($data)
            ->setTemplateDir(__DIR__ . '/../' . $viewsPath)
            ->setTemplateVar('appViewContent')
            ->setLayoutFile($layoutFile)
            ->setTemplate($view)
            ->setUseLayout($useLayout)
            ->setStatus($status)
            ->generatedView();

        return $this->getHttpResponse($view, $status);
    }

    private function getAutoDetectedView(): string
    {
        $useCaseFileName = '';
        $backtrace = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        if (isset($backtrace[1]['file']))
            $useCaseFileName = $backtrace[1]['file'];

        $useCaseFileName = \strrchr($useCaseFileName, '/');
        $useCaseFileName = $useCaseFileName ? $useCaseFileName : '';

        $useCaseFileName = \strstr($useCaseFileName, 'UseCase.php', true);
        $useCaseFileName = $useCaseFileName ? $useCaseFileName : '';

        return \trim($useCaseFileName, '/');
    }

    protected function redirect(string $link, string $method = 'location', int $seconds = 0): void
    {
        Redirect::x($link, $this->cfg->getUrlIndex(), $method, $seconds);
        exit;
    }

    protected function getUriSegment(int $key): ?string
    {
        return PathSegment::x($key, $_SERVER['REQUEST_URI']);
    }

    /**
     * Execute a "UseCase" class' method and return response
     *
     * @param ServerRequestInterface $serverRequest
     * @param ?mixed[] $args
     * @return ResponseInterface
     */
    protected function getUseCaseResponse(ServerRequestInterface $serverRequest, ?array $args, BaseControllerAbstract $useCase): ResponseInterface
    {
        $useCaseMethodName = \lcfirst($this->getUseCaseName($useCase));

        $useCase = $this->factoryService->getInstance(\get_class($useCase));

        return $useCase->$useCaseMethodName($serverRequest, $args);
    }

    /**
     * Get the "UseCase" name from a "UseCase" object
     *
     * @param BaseControllerAbstract $useCase
     * @return string
     */
    private function getUseCaseName(BaseControllerAbstract $useCase): string
    {
        $useCaseFqn = \get_class($useCase);
        $classParts = \explode('\\', $useCaseFqn);
        $useCaseClassName = $classParts[2];

        return \str_replace('UseCase', '', $useCaseClassName);
    }

    protected function stop(string $message): ResponseInterface
    {
        $this->data['stopMessage'] = $message;

        return $this->getView([], 'stop', false);
    }

}