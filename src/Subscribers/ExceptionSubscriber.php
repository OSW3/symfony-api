<?php 
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\ResponseService;
use OSW3\Api\Service\TemplateService;
use OSW3\Api\Service\ConfigurationService;
use OSW3\Api\Service\ResponseStatusService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;

/**
 * Handles exception-related tasks during the request lifecycle.
 * 
 * @stage 0
 * @priority 0
 * @before -
 * @after -
 */
class ExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ResponseService $responseService,
        private readonly ResponseStatusService $statusService,
        private readonly TemplateService $templateService,
        private readonly ConfigurationService $configurationService,
    ){}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 0]
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $exception = $event->getThrowable();

        $statusCode = $exception instanceof HttpExceptionInterface
            ? $exception->getStatusCode()
            : Response::HTTP_INTERNAL_SERVER_ERROR;

        $statusText = $exception->getMessage() ?: Response::$statusTexts[$statusCode];

        // if (!in_array($statusCode, [Response::HTTP_NOT_FOUND, Response::HTTP_FORBIDDEN], true)) {
        //     return;
        // }
        if ($statusCode < 400 || $statusCode >= 500) {
        // if ($statusCode < 400) {
            return;
        }


        // dd($event->getRequest()->attributes);
        $this->statusService->setCode($statusCode);

        // Prepare data for the response
        $data            = [];
        $data['code']    = $statusCode;
        $data['message'] = $statusText;
        $this->responseService->setData($data);

        // Prepare the response using templates
        $template        = $this->templateService->getTemplate('error');
        $content         = $this->templateService->parse($template, $data);
        $this->responseService->setContent($content);

        // Set the response in the event
        $event->setResponse($this->responseService->build());
    }
}
