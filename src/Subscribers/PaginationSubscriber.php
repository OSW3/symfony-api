<?php 
namespace OSW3\Api\Subscribers;

use OSW3\Api\Enum\Pagination\Headers;
use OSW3\Api\Service\PaginationService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles pagination headers in API responses.
 * Used to provide pagination metadata to clients.
 */
final class PaginationSubscriber implements EventSubscriberInterface 
{
    public function __construct(
        private readonly PaginationService $paginationService,
    ){}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onResponse', 0]
        ];
    }
    
    public function onResponse(ResponseEvent $event): void 
    {
        // Is main request
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();


        if ($this->paginationService->isEnabled()) 
        {
            $response->headers->set(
                Headers::TOTAL_COUNT->value,
                $this->paginationService->getTotal()
            );
            
            $response->headers->set(
                Headers::TOTAL_PAGES->value,
                $this->paginationService->getTotalPages()
            );

            $response->headers->set(
                Headers::PER_PAGE->value,
                $this->paginationService->getLimit()
            );

            $response->headers->set(
                Headers::CURRENT_PAGE->value,
                $this->paginationService->getPage()
            );

            $response->headers->set(
                Headers::NEXT_PAGE->value,
                $this->paginationService->getNext()
            );

            $response->headers->set(
                Headers::PREVIOUS_PAGE->value,
                $this->paginationService->getPrevious()
            );

            $response->headers->set(
                Headers::SELF_PAGE->value,
                $this->paginationService->getSelf()
            );

            $response->headers->set(
                Headers::FIRST_PAGE->value,
                $this->paginationService->getFirst()
            );

            $response->headers->set(
                Headers::LAST_PAGE->value,
                $this->paginationService->getLast()
            );
        }
    }
}