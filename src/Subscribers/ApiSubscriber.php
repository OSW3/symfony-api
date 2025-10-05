<?php 
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\RequestService;
use OSW3\Api\Service\RepositoryService;
use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ApiSubscriber implements EventSubscriberInterface 
{
    public function __construct(
        // Pour le dev
        private ConfigurationService $configuration,
        
        private RequestService $requestService,
        // private RepositoryService $repository,
    ){}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 0],
            KernelEvents::RESPONSE => ['onResponse', 10],
        ];
    }

    public function onRequest(RequestEvent $event): void 
    {


        // dd($event);


        // Check if the current route is defined in the API config
        if (!$this->requestService->support()) {
            return;
        }

        // // Check if the current route has a defined in the API config
        // if ($event->getRequest()->attributes->get('_controller') != null) 
        // {
        //     return;
        // }
        
        // $params = $event->getRequest()->attributes->get('_route_params') ?? [];
        // $method = $event->getRequest()->getMethod();
        // $id     = $params['id'] ?? null;

        // match ($method) {
        //     Request::METHOD_GET    => $id ? $this->findOne($id) : $this->findAll(),
        //     Request::METHOD_PUT    => $this->update($id),
        //     Request::METHOD_POST   => $this->create(),
        //     Request::METHOD_PATCH  => $this->update($id),
        //     Request::METHOD_DELETE => $this->delete($id),
        // };


        dd($event);



        // $content = [
        //     'response' => "Test 11"
        // ];
        // $statusCode = 200;
        // $response = new JsonResponse($content, $statusCode);


        // // Set Response
        // // --

        // $event->setResponse($response);
    }
    
    public function onResponse(ResponseEvent $event): void
    {
        // Check if the current route is defined in the API config
        if (!$this->requestService->support()) 
        {
            return;
        }

        // // Check if the current route has a defined in the API config
        // if ($event->getRequest()->attributes->get('_controller') != null) 
        // {
        //     return;
        // }



        dd($event);

        $content = [
            'response' => "Test 222"
        ];
        $statusCode = 200;
        $response = new JsonResponse($content, $statusCode);


        // Set Response
        // --

        $event->setResponse($response);
    }


    private function findAll(): void
    {
        // $data = $this->repository->findAll();

        // dump($data);
        // $this->responseService->setData($data);
    }

    private function findOne(int $id): void
    {
        dump('FIND ONE');

        // $data = $this->repository->findOne($id);
        // empty($data)
        //     ? $this->headersService->setStatusCode(Response::HTTP_NOT_FOUND)
        //     : $this->responseService->setData($data)
        // ;
    }

    private function create(): void
    {
        // $data = $this->repository->create();
        
        // if (empty($data))
        // {
        //     $this->headersService->setStatusCode(Response::HTTP_BAD_REQUEST);
        // }
        // else 
        // {
        //     $this->headersService->setStatusCode(Response::HTTP_CREATED);
        //     $this->responseService->setData($data);
        // }
    }

    private function update(int $id): void 
    {
        // $data = $this->repository->patch($id);
        
        // empty($data)
        //     ? $this->headersService->setStatusCode(Response::HTTP_BAD_REQUEST)
        //     : $this->responseService->setData($data)
        // ;
    }

    private function delete(int $id): void 
    {
        // $this->repository->delete($id)
        //     ? $this->headersService->setStatusCode(Response::HTTP_NO_CONTENT)
        //     : $this->headersService->setStatusCode(Response::HTTP_BAD_REQUEST)
        // ;
    }





    private function dumpConfiguration(): void 
    {
        $providers = $this->configuration->getAllProviders();
        // dump( $providers );

        $provider = $this->configuration->getProvider('my_custom_api_v1'); 
        // dump( $provider );

        $version = $this->configuration->getVersion('my_custom_api_v1');
        // dump( $version );

        $globalRouteNamePattern = $this->configuration->getRouteNamePattern('my_custom_api_v1'); 
        $collectionRouteNamePattern = $this->configuration->getRouteNamePattern('my_custom_api_v1', 'App\Entity\Book');
        $endpointRouteName = $this->configuration->getRouteNamePattern('my_custom_api_v1', 'App\Entity\Book', 'index');
        // dump( $globalRouteNamePattern );
        // dump( $collectionRouteNamePattern );
        // dump( $endpointRouteName );

        $globalRoutePrefix = $this->configuration->getRoutePrefix('my_custom_api_v1'); 
        $collectionRoutePrefix = $this->configuration->getRoutePrefix('my_custom_api_v1', 'App\Entity\Book');
        $endpointRoutePrefix = $this->configuration->getRoutePrefix('my_custom_api_v1', 'App\Entity\Book', 'index');
        // dump( $globalRoutePrefix );
        // dump( $collectionRoutePrefix );
        // dump( $endpointRoutePrefix );

        $isGlobalSearchEnabled = $this->configuration->isSearchEnabled('my_custom_api_v1');
        $isCollectionSearchEnabled = $this->configuration->isSearchEnabled('my_custom_api_v1', 'App\Entity\Book');
        // dump( $isGlobalSearchEnabled );
        // dump( $isCollectionSearchEnabled );

        $getCollectionSearchFields = $this->configuration->getSearchFields('my_custom_api_v1', 'App\Entity\Book');
        // dump($getCollectionSearchFields);

        $isGlobalPaginationEnabled = $this->configuration->isPaginationEnabled('my_custom_api_v1');
        // dump( $isGlobalPaginationEnabled );

        $getGlobalPaginationPerPage = $this->configuration->getPaginationPerPage('my_custom_api_v1');
        $getCollectionPaginationPerPage = $this->configuration->getPaginationPerPage('my_custom_api_v1', 'App\Entity\Book');
        // dump( $getGlobalPaginationPerPage );
        // dump($getCollectionPaginationPerPage);

        $getGlobalPaginationMaxPerPage = $this->configuration->getPaginationMaxPerPage('my_custom_api_v1');
        // dump( $getGlobalPaginationMaxPerPage );

        $hasUrlSupport = $this->configuration->hasUrlSupport('my_custom_api_v1');
        // dump( $hasUrlSupport );

        $isUrlAbsolute = $this->configuration->isUrlAbsolute('my_custom_api_v1');
        // dump( $isUrlAbsolute );

        $collections = $this->configuration->getCollections('my_custom_api_v1');
        $collection = $this->configuration->getCollection('my_custom_api_v1', 'App\Entity\Book');
        // dump($collections);
        // dump($collection);

        $endpoints = $this->configuration->getEndpoints('my_custom_api_v1', 'App\Entity\Book');
        $endpoint = $this->configuration->getEndpoint('my_custom_api_v1', 'App\Entity\Book', 'index');
        // dump($endpoints);
        // dump($endpoint);


        $endpointRoute = $this->configuration->getEndpointRoute('my_custom_api_v1', 'App\Entity\Book', 'index');
        // dump($endpointRoute);
        $endpointRouteName = $this->configuration->getEndpointRouteName('my_custom_api_v1', 'App\Entity\Book', 'index');
        // dump($endpointRouteName);
        $endpointRouteMethods = $this->configuration->getEndpointRouteMethods('my_custom_api_v1', 'App\Entity\Book', 'index');
        // dump($endpointRouteMethods);
        $endpointRouteController = $this->configuration->getEndpointRouteController('my_custom_api_v1', 'App\Entity\Book', 'index');
        // dump($endpointRouteController);
        $endpointRouteOptions = $this->configuration->getEndpointRouteOptions('my_custom_api_v1', 'App\Entity\Book', 'index');
        // dump($endpointRouteOptions);
        $endpointRouteCondition = $this->configuration->getEndpointRouteCondition('my_custom_api_v1', 'App\Entity\Book', 'index');
        // dump($endpointRouteCondition);
        $endpointRouteRequirements = $this->configuration->getEndpointRouteRequirements('my_custom_api_v1', 'App\Entity\Book', 'index');
        // dump($endpointRouteRequirements);
        

        $repositoryClass = $this->configuration->getRepositoryClass('my_custom_api_v1', 'App\Entity\Book', 'index');
        // dump($repositoryClass);
        $method = $this->configuration->getMethod('my_custom_api_v1', 'App\Entity\Book', 'index');
        // dump($method);
        $criteria = $this->configuration->getCriteria('my_custom_api_v1', 'App\Entity\Book', 'index');
        // dump($criteria);
        $orderBy = $this->configuration->getOrderBy('my_custom_api_v1', 'App\Entity\Book', 'index');
        // dump($orderBy);
        $limit = $this->configuration->getLimit('my_custom_api_v1', 'App\Entity\Book', 'index');
        // dump($limit);
        $fetchMode = $this->configuration->getFetchMode('my_custom_api_v1', 'App\Entity\Book', 'index');
        // dump($fetchMode);

        
        $metadata = $this->configuration->getMetadata('my_custom_api_v1', 'App\Entity\Book', 'index');
        // dump($metadata);
        

        $granted = $this->configuration->getGranted('my_custom_api_v1', 'App\Entity\Book', 'index');
        // dump($granted);
        $voter = $this->configuration->getVoter('my_custom_api_v1', 'App\Entity\Book', 'index');
        // dump($voter);
        

        $hooks = $this->configuration->getHooks('my_custom_api_v1', 'App\Entity\Book', 'index');
        // dump($hooks);
        

        $serializeGroups = $this->configuration->getSerializeGroups('my_custom_api_v1', 'App\Entity\Book', 'index');
        // dump($serializeGroups);
        $serializeTransformer = $this->configuration->getSerializeTransformer('my_custom_api_v1', 'App\Entity\Book', 'index');
        // dump($serializeTransformer);
        

        $transformer = $this->configuration->getTransformer('my_custom_api_v1', 'App\Entity\Book', 'index');
        // dump($transformer);
        

        $rateLimit = $this->configuration->getRateLimit('my_custom_api_v1', 'App\Entity\Book', 'index');
        // dump($rateLimit);
        $rateLimitByRole = $this->configuration->getRateLimitByRole('my_custom_api_v1', 'App\Entity\Book', 'index');
        // dump($rateLimitByRole);
        $rateLimitByUser = $this->configuration->getRateLimitByUser('my_custom_api_v1', 'App\Entity\Book', 'index');
        // dump($rateLimitByUser);
        
    }
}