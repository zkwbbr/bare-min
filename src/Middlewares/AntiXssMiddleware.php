<?php

declare(strict_types=1);

namespace App\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\StreamFactory;
use voku\helper\AntiXSS;

/**
 * Globally protect user input from XSS
 */
final class AntiXssMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $contentType = $request->getHeaderLine('content-type');

        // ------------------------------------------------

        if ($contentType == 'application/json')
            return $this->processedJson($request, $handler);

        // ------------------------------------------------

        $formDataContentTypes = [
            'application/x-www-form-urlencoded',
            'multipart/form-data'
        ];

        if (\in_array($contentType, $formDataContentTypes))
            return $this->processedFormData($request, $handler);

        // ------------------------------------------------

        return $handler->handle($request);
    }

    /**
     * Recursive version of \array_map();
     *
     * Credit: https://stackoverflow.com/a/39637749/748789
     *
     * @param callable $callback
     * @param mixed[] $array
     * @return mixed[]
     */
    private function arrayMapRecursive(callable $callback, array $array): array
    {
        $func = function ($item) use (&$func, &$callback) {
            return \is_array($item) ? \array_map($func, $item) : \call_user_func($callback, $item);
        };

        return \array_map($func, $array);
    }

    private function processedJson(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $stream = $request->getBody();
        $stream->rewind();
        $contents = $stream->getContents();
        $decodedContents = (array) \json_decode($contents, true);

        // ------------------------------------------------

        $data = $this->arrayMapRecursive(
            function ($item) {
                $s = (new AntiXSS())->xss_clean($item); // @phpstan-ignore-line
                if (\is_string($s))
                    $s = \strip_tags($s); // we need to \strip_tags() because AntiXSS does not strip all HTML tags
                return $s;
            },
            $decodedContents
        );

        // ------------------------------------------------

        $streamFactory = new StreamFactory;
        $stream = $streamFactory->createStream((string) \json_encode($data));
        $request = $request->withBody($stream);

        // ------------------------------------------------

        return $handler->handle($request);
    }

    private function processedFormData(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();

        // ------------------------------------------------

        $data = $this->arrayMapRecursive(
            function ($item) {
                $s = (new AntiXSS())->xss_clean($item); // @phpstan-ignore-line
                if (\is_string($s))
                    $s = \strip_tags($s); // we need to \strip_tags() because AntiXSS does not strip all HTML tags
                return $s;
            },
            $parsedBody
        );

        // ------------------------------------------------

        $request = $request->withParsedBody($data);

        return $handler->handle($request);
    }

}