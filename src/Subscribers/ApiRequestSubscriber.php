<?php 
namespace OSW3\Api\Subscribers;

use OSW3\Api\Service\RequestService;
use OSW3\Api\Service\RepositoryService;
use OSW3\Api\Service\ConfigurationService;
use OSW3\Api\Service\ResponseService;
use OSW3\Api\Service\SupportService;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ApiRequestSubscriber implements EventSubscriberInterface 
{
    public function __construct(
        private readonly ConfigurationService $configuration,
        private readonly RequestService $requestService,
        private readonly RepositoryService $repository,
        private readonly SupportService $supportService,
        private readonly ResponseService $responseService,
    ){}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest'],
        ];
    }
    
    public function onRequest(RequestEvent $event): void 
    {
        // dd($this->supportService->supports());
        // Check if the request is not a valid defined route
        if (!$this->supportService->supports()) {
            return;
        }
        
        // Exit the process when a valid custom controller is defined
        if ($this->supportService->hasCustomController()) {
            return;
        }


        // Retrieve data from database
        // --

        $data = $this->repository->resolve();


        // Data serialization
        // --


        // Build the response
        // --


        // Set Response
        // --

        $event->setResponse(new JsonResponse(
            $this->responseService->buildResponse($data), 
            $this->responseService->getResponseStatusCode()
        ));
    }













    





    // private function dumpConfiguration(): void 
    // {
    //     $providers = $this->configuration->getAllProviders();
    //     // dump( $providers );

    //     $provider = $this->configuration->getProvider('my_custom_api_v1'); 
    //     // dump( $provider );

    //     $version = $this->configuration->getVersion('my_custom_api_v1');
    //     // dump( $version );

    //     $globalRouteNamePattern = $this->configuration->getRouteNamePattern('my_custom_api_v1'); 
    //     $collectionRouteNamePattern = $this->configuration->getRouteNamePattern('my_custom_api_v1', 'App\Entity\Book');
    //     $endpointRouteName = $this->configuration->getRouteNamePattern('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump( $globalRouteNamePattern );
    //     // dump( $collectionRouteNamePattern );
    //     // dump( $endpointRouteName );

    //     $globalRoutePrefix = $this->configuration->getRoutePrefix('my_custom_api_v1'); 
    //     $collectionRoutePrefix = $this->configuration->getRoutePrefix('my_custom_api_v1', 'App\Entity\Book');
    //     $endpointRoutePrefix = $this->configuration->getRoutePrefix('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump( $globalRoutePrefix );
    //     // dump( $collectionRoutePrefix );
    //     // dump( $endpointRoutePrefix );

    //     $isGlobalSearchEnabled = $this->configuration->isSearchEnabled('my_custom_api_v1');
    //     $isCollectionSearchEnabled = $this->configuration->isSearchEnabled('my_custom_api_v1', 'App\Entity\Book');
    //     // dump( $isGlobalSearchEnabled );
    //     // dump( $isCollectionSearchEnabled );

    //     $getCollectionSearchFields = $this->configuration->getSearchFields('my_custom_api_v1', 'App\Entity\Book');
    //     // dump($getCollectionSearchFields);

    //     $isGlobalPaginationEnabled = $this->configuration->isPaginationEnabled('my_custom_api_v1');
    //     // dump( $isGlobalPaginationEnabled );

    //     $getGlobalPaginationPerPage = $this->configuration->getPaginationPerPage('my_custom_api_v1');
    //     $getCollectionPaginationPerPage = $this->configuration->getPaginationPerPage('my_custom_api_v1', 'App\Entity\Book');
    //     // dump( $getGlobalPaginationPerPage );
    //     // dump($getCollectionPaginationPerPage);

    //     $getGlobalPaginationMaxPerPage = $this->configuration->getPaginationMaxPerPage('my_custom_api_v1');
    //     // dump( $getGlobalPaginationMaxPerPage );

    //     $hasUrlSupport = $this->configuration->hasUrlSupport('my_custom_api_v1');
    //     // dump( $hasUrlSupport );

    //     $isUrlAbsolute = $this->configuration->isUrlAbsolute('my_custom_api_v1');
    //     // dump( $isUrlAbsolute );

    //     $collections = $this->configuration->getCollections('my_custom_api_v1');
    //     $collection = $this->configuration->getCollection('my_custom_api_v1', 'App\Entity\Book');
    //     // dump($collections);
    //     // dump($collection);

    //     $endpoints = $this->configuration->getEndpoints('my_custom_api_v1', 'App\Entity\Book');
    //     $endpoint = $this->configuration->getEndpoint('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($endpoints);
    //     // dump($endpoint);


    //     $endpointRoute = $this->configuration->getEndpointRoute('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($endpointRoute);
    //     $endpointRouteName = $this->configuration->getEndpointRouteName('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($endpointRouteName);
    //     $endpointRouteMethods = $this->configuration->getEndpointRouteMethods('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($endpointRouteMethods);
    //     $endpointRouteController = $this->configuration->getEndpointRouteController('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($endpointRouteController);
    //     $endpointRouteOptions = $this->configuration->getEndpointRouteOptions('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($endpointRouteOptions);
    //     $endpointRouteCondition = $this->configuration->getEndpointRouteCondition('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($endpointRouteCondition);
    //     $endpointRouteRequirements = $this->configuration->getEndpointRouteRequirements('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($endpointRouteRequirements);
        

    //     $repositoryInstance = $this->configuration->getRepository('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($repositoryInstance);
    //     $method = $this->configuration->getMethod('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($method);
    //     $criteria = $this->configuration->getCriteria('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($criteria);
    //     $orderBy = $this->configuration->getOrderBy('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($orderBy);
    //     $limit = $this->configuration->getLimit('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($limit);
    //     $fetchMode = $this->configuration->getFetchMode('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($fetchMode);

        
    //     $metadata = $this->configuration->getMetadata('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($metadata);
        

    //     $granted = $this->configuration->getGranted('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($granted);
    //     $voter = $this->configuration->getVoter('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($voter);
        

    //     $hooks = $this->configuration->getHooks('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($hooks);
        

    //     $serializeGroups = $this->configuration->getSerializeGroups('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($serializeGroups);
    //     $serializeTransformer = $this->configuration->getSerializeTransformer('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($serializeTransformer);
        

    //     $transformer = $this->configuration->getTransformer('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($transformer);
        

    //     $rateLimit = $this->configuration->getRateLimit('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($rateLimit);
    //     $rateLimitByRole = $this->configuration->getRateLimitByRole('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($rateLimitByRole);
    //     $rateLimitByUser = $this->configuration->getRateLimitByUser('my_custom_api_v1', 'App\Entity\Book', 'index');
    //     // dump($rateLimitByUser);
        
    // }
}