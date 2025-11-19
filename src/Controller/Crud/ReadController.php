<?php 
namespace OSW3\Api\Controller\Crud;

use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\TemplateService;
use OSW3\Api\Service\SerializeService;
use Doctrine\Persistence\ManagerRegistry;
use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ReadController extends AbstractController
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
        private readonly ConfigurationService $configurationService,
    ){
        $this->provider    = $contextService->getContext('provider');
        $this->segment     = $contextService->getContext('segment');
        $this->collection  = $contextService->getContext('collection');
        $this->endpoint    = $contextService->getContext('endpoint');
        $this->entityClass = $this->collection;
        $this->repository  = $doctrine->getRepository($this->entityClass);
        $this->method      = $configurationService->getRepositoryMethod(
                                provider  : $this->provider,
                                segment   : $this->segment,
                                collection: $this->collection,
                                endpoint  : $this->endpoint
                            )['method'] ?? null;
    }
    
    public function execute(int|string $id): JsonResponse
    {
        // Get repository method
        $method = $this->method ?? 'find';

        // Get data from repository
        $raw = $this->repository->$method($id);

        // Normalize data
        $normalized = $this->serializeService->normalize($raw);

        // Define template type
        $this->templateService->setType(TemplateService::TEMPLATE_TYPE_SINGLE);

        // Return response
        return $this->json($normalized);
    }
}