<?php 
namespace OSW3\Api\Controller\Crud;

use OSW3\Api\Enum\Template\Type;
use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\TemplateService;
use OSW3\Api\Service\SerializeService;
use OSW3\Api\Service\PaginationService;
use Doctrine\Persistence\ManagerRegistry;
use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class IndexController extends AbstractController
{
    private ?string $method;
    private string $provider;
    private string $segment;
    private string $endpoint;
    private string $collection;
    private object $repository;
    private string $entityClass;

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly ContextService $contextService,
        private readonly TemplateService $templateService,
        private readonly SerializeService $serializeService,
        private readonly PaginationService $paginationService,
        private readonly ConfigurationService $configurationService,
    ){
        $this->provider    = $contextService->getContext('provider');
        $this->segment     = $contextService->getContext('segment');
        $this->collection  = $contextService->getContext('collection');
        $this->endpoint    = $contextService->getContext('endpoint');
        $this->entityClass = $this->collection;
        $this->repository  = $doctrine->getRepository($this->entityClass);
        $this->method      = $configurationService->getRepositoryMethod($this->provider, $this->segment, $this->collection, $this->endpoint)['method'] ?? null;
    }

    public function execute(): JsonResponse
    {
        // Get repository method
        $method = $this->method ?? 'findBy';

        // Get criteria
        $criteria = $this->configurationService->getCriteria(
            $this->provider, 
            $this->segment,
            $this->collection, 
            $this->endpoint
        );

        // Get order
        $order = $this->configurationService->getOrderBy(
            $this->provider, 
            $this->segment,
            $this->collection, 
            $this->endpoint
        );

        // Get pagination limit
        $limit = $this->paginationService->getLimit();
        $limit = $limit > 0 ? $limit : null;

        // Get pagination offset
        $offset = $this->paginationService->getOffset();

        // Set total items
        $this->paginationService->setTotal($this->repository->count($criteria)); 

        // Get data from repository
        $raw = $this->repository->$method(
            $criteria, 
            $order, 
            $limit, 
            $offset
        );

        // Normalize data
        $normalized = $this->serializeService->normalize($raw);

        // Define template type
        $this->templateService->setType(Type::LIST->value);

        // Return response
        return $this->json($normalized);
    }
}