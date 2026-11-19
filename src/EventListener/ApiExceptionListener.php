<?php
declare (strict_types=1);

namespace App\EventListener;

use App\Utils\JsonResponse;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Turn any exception thrown while handling API request into JSON error response,
 * so API callers never get HTML error page
 */
#[AsEventListener(event: KernelEvents::EXCEPTION)]
class ApiExceptionListener {

    const API_PATH_PREFIX = '/api/';

    public function __construct(
        #[Autowire('%kernel.debug%')]
        private bool $debug,
    ) {
    }

    /**
     * @param ExceptionEvent $event
     * @return void
     */
    public function __invoke(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), self::API_PATH_PREFIX)) {
            return;
        }

        $exception = $event->getThrowable();

        if ($exception instanceof HttpExceptionInterface) {
            // e.g. unknown route (404), wrong HTTP method (405), non-scalar query parameter (400)
            $statusCode = $exception->getStatusCode();
            $message = $exception->getMessage();
        } else {
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            // don't leak internals in production
            $message = $this->debug ? $exception->getMessage() : 'Unexpected error';
        }

        $response = JsonResponse::prepareErrorJsonResponse($message, $statusCode, true);

        if ($exception instanceof HttpExceptionInterface) {
            // keep headers such as 'Allow' for 405 responses
            $response->headers->add($exception->getHeaders());
        }

        $event->setResponse($response);
    }

}
