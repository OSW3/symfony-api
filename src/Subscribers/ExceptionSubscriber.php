<?php 
namespace OSW3\Api\Subscribers;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        // private readonly ResponseService $responseService,
        // private readonly ResponseStatusService $statusService,
        // private readonly TemplateService $templateService,
        // private readonly ConfigurationService $configurationService,
    ){}

    public static function getSubscribedEvents(): array
    {
        return [
            // KernelEvents::EXCEPTION => ['onKernelException', 0]
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        // TODO: Prise ne charge des erreur 404 si le provider n'est pas activé
        
        // dump('0 - ExceptionSubscriber::onKernelException');

        if (!$event->isMainRequest()) {
            return;
        }

        // Get current response
        // $response = $event->getResponse();

        // Retrieve the exception object from the event
        $exception = $event->getThrowable();
        
        // Determine the status code and status text
        $statusCode = method_exists($exception, 'getStatusCode')
            ? $exception->{'getStatusCode'}()
            : Response::HTTP_INTERNAL_SERVER_ERROR;

        $statusText = method_exists($exception, 'getMessage')
            ? $exception->getMessage()
            : Response::$statusTexts[$statusCode];

        // Only handle client errors (4xx)
        if ($statusCode < 400 || $statusCode >= 500) {
            // return;
        }



        $response = new JsonResponse();
        
        // Set the response status code
        $response->setStatusCode($statusCode);


        // $template = $this->templateService->getTemplate('error');





        // Set the response content
        $content = '{"error": "' . $statusText . '"}';
        $response->setContent($content);

        // Stop the event propagation
        $event->setResponse($response);
        $event->stopPropagation();




        // dd($template, $response, $statusText);
        // // dd($event->getRequest()->attributes);
        // // $this->statusService->setCode($statusCode);

        // // Prepare data for the response
        // $data            = [];
        // $data['code']    = $statusCode;
        // $data['message'] = $statusText;
        // $this->responseService->setData($data);

        // // Prepare the response using templates
        // $template        = $this->templateService->getTemplate('error');
        // $content         = $this->templateService->parse($template, $data);
        // $this->responseService->setContent($content);






        // Set the response in the event
        // $event->setResponse($this->responseService->build());
    }
}
