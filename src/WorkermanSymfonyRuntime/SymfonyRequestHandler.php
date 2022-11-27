<?php

declare(strict_types=1);

namespace Manyou\WorkermanSymfonyRuntime;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

class SymfonyRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private HttpKernelInterface $kernel,
        private HttpFoundationFactoryInterface $httpFoundationFactory,
        private HttpMessageFactoryInterface $httpMessageFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $sfRequest  = $this->httpFoundationFactory->createRequest($request);

        try {
            $sfResponse = $this->kernel->handle($sfRequest);
        } catch (\Throwable $exception) {
            $fe = FlattenException::createFromThrowable($exception);
            $sfResponse = new \Symfony\Component\HttpFoundation\Response($fe->getAsString());
        }

        $response   = $this->httpMessageFactory->createResponse($sfResponse);

        if ($this->kernel instanceof TerminableInterface) {
            $this->kernel->terminate($sfRequest, $sfResponse);
        }

        return $response;
    }
}
