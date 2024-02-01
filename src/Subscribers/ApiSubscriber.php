<?php 
namespace OSW3\Api\Subscribers;

use OSW3\Api\Services\RouteService;
use OSW3\Api\Services\HeadersService;
use OSW3\Api\Services\RequestService;
use OSW3\Api\Services\ResponseService;
use Doctrine\ORM\EntityManagerInterface;
use OSW3\Api\Services\PaginationService;
use OSW3\Api\Services\RepositoryService;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ApiSubscriber implements EventSubscriberInterface 
{
    private RepositoryService $repository;

    public function __construct(
        private ResponseService $responseService,
        private RequestService $requestService,
        private HeadersService $headersService,
        private EntityManagerInterface $entityManager,
        private PaginationService $paginationService,
        private RouteService $routeService,
        private RequestStack $requestStack,

    ){}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onRequest',
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        $this->routeService->addCollection();

        if (!$this->requestService->supports())
        {
            return;
        }
        
        // Start the Repository Service
        $this->repository = new RepositoryService( 
            $this->entityManager, 
            $this->requestService, 
            $this->headersService, 
            $this->responseService, 
            $this->paginationService,
            $this->requestStack,
        );

        // Get the current request ID
        $id = $this->requestService->getId();
        
        match ($event->getRequest()->getMethod())
        {
            Request::METHOD_GET    => $id ? $this->findOne($id) : $this->findAll(),
            Request::METHOD_PUT    => $id ? $this->update($id) : $this->create(),
            Request::METHOD_POST   => $this->create(),
            Request::METHOD_PATCH  => $this->update($id),
            Request::METHOD_DELETE => $this->delete($id),
        };
    }
    
    public function onResponse(ResponseEvent $event): void
    {
        if (!$this->requestService->supports())
        {
            return;
        }

        $content    = $this->responseService->getContent();
        $statusCode = $this->headersService->getStatusCode();
        $response   = new JsonResponse($content, $statusCode);


        // SET RESPONSE HEADERS
        // --

        $this->headersService->setResponse($response);
        $this->headersService->builder();
        

        // Set Response
        // --

        // dd('k');
        $event->setResponse($response);
    }


    private function findAll(): void
    {
        $data = $this->repository->findAll();
        $this->responseService->setData($data);
    }

    private function findOne(int $id): void
    {
        $data = $this->repository->findOne($id);
        empty($data)
            ? $this->headersService->setStatusCode(Response::HTTP_NOT_FOUND)
            : $this->responseService->setData($data)
        ;
    }

    private function create(): void
    {
        $data = $this->repository->create();
        
        if (empty($data))
        {
            $this->headersService->setStatusCode(Response::HTTP_BAD_REQUEST);
        }
        else 
        {
            $this->headersService->setStatusCode(Response::HTTP_CREATED);
            $this->responseService->setData($data);
        }
    }

    private function update(int $id): void 
    {
        $data = $this->repository->patch($id);
        
        empty($data)
            ? $this->headersService->setStatusCode(Response::HTTP_BAD_REQUEST)
            : $this->responseService->setData($data)
        ;
    }

    private function delete(int $id): void 
    {
        $this->repository->delete($id)
            ? $this->headersService->setStatusCode(Response::HTTP_NO_CONTENT)
            : $this->headersService->setStatusCode(Response::HTTP_BAD_REQUEST)
        ;
    }
}