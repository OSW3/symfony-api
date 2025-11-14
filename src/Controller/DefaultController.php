<?php 
namespace OSW3\Api\Controller;

use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\RequestService;
use OSW3\Api\Service\ResponseService;
use OSW3\Api\Service\TemplateService;
use OSW3\Api\Service\SerializeService;
use OSW3\Api\Service\PaginationService;
use Doctrine\Persistence\ManagerRegistry;
use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class DefaultController extends AbstractController
{
    private string $provider;
    private string $collection;
    private string $endpoint;
    private string $entityClass;
    private object $repository;
    private ?string $method;
    private $em;

    public function __construct(
        private readonly RequestService $requestService,
        private readonly ContextService $contextService,
        private readonly ResponseService $responseService,
        private readonly TemplateService $templateService,
        private readonly SerializeService $serializeService,
        private readonly PaginationService $paginationService,
        private readonly ConfigurationService $configurationService,
        private readonly ManagerRegistry $doctrine
    ) {
        $this->em          = $doctrine->getManager();
        $this->provider    = $contextService->getContext('provider');
        $this->collection  = $contextService->getContext('collection');
        $this->endpoint    = $contextService->getContext('endpoint');
        $this->entityClass = $this->collection;
        $this->repository  = $doctrine->getRepository($this->entityClass);
        $this->method      = $configurationService->getRepositoryMethod($this->provider, $this->collection, $this->endpoint)['method'] ?? null;
    }

    public function index(): JsonResponse
    {
        // Get repository method
        $method = $this->method ?? 'findBy';

        // Get criteria
        $criteria = $this->configurationService->getCriteria(
            $this->provider, 
            $this->collection, 
            $this->endpoint
        );

        // Get order
        $order = $this->configurationService->getOrderBy(
            $this->provider, 
            $this->collection, 
            $this->endpoint
        );

        // Get pagination limit
        $limit = $this->paginationService->getLimit();

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
        $this->templateService->setType(TemplateService::TEMPLATE_TYPE_LIST);

        // Return response
        return $this->json($normalized);
    }

    public function create(): JsonResponse
    {
        // Get entity class
        $entityClass = $this->entityClass;

        // Get data from request
        $data = json_decode($this->requestService->getCurrentRequest()->getContent(), true) ?? [];

        // Populate entity with data
        $entity = $this->serializeService->denormalize($data, $entityClass);

        // Prepare entity metadata
        $metadata    = $this->em->getClassMetadata($entityClass);
        $relations   = [];


        // Handle relations
        // --

        foreach ($metadata->associationMappings as $field => $mapping) {

            // Skip if no data provided for this relation
            if (!isset($data[$field])) {
                continue;
            }

            $targetEntity  = $mapping['targetEntity'];
            $relationValue = $data[$field];

            // Find and link the relation by its identifier
            // when a scalar value (string|int) is provided
            if (is_scalar($relationValue)) {
                $related = $this->em->getRepository($targetEntity)->find($relationValue);
                if ($related) {
                    $relations[$field] = $related;
                }
                unset($data[$field]);
                continue;
            }

            // Exit if the relation value is not an array
            if (!is_array($relationValue)) {
                continue;
            }
            
            // Get the target entity metadata
            $targetMetadata  = $this->em->getClassMetadata($targetEntity);
            $identifierField = $targetMetadata->{'getSingleIdentifierFieldName'}();

            // Try to find existing entity by its identifier
            $existing = null;
            if (isset($relationValue[$identifierField])) {
                $existing = $this->em->getRepository($targetEntity)->find($relationValue[$identifierField]);
            }

            // Link existing entity
            if ($existing) {
                $relations[$field] = $existing;
                unset($data[$field]);
                continue;
            }

            // // Skip creation if not allowed
            // if (!$allowRelationCreate) {
            //     continue;
            // }

            // $related = $this->serializeService->denormalize($relationValue, $targetEntity);
            // $em->persist($related);
            // $data[$field] = $related;
        }


        // Create the entity
        // --


        // Add relations to entity
        foreach ($relations as $field => $related) {
            $accessor = PropertyAccess::createPropertyAccessor();
            $accessor->setValue($entity, $field, $related);
        }

        // Persist entity
        $this->em->persist($entity);
        $this->em->flush();


        // Response processing
        // --

        // Normalize data and set response
        $normalized = $this->serializeService->normalize($entity);
        // $this->responseService->setData($normalized);

        // Get template and parse content
        // $template = $this->templateService->getTemplate('item');
        // $content  = $this->templateService->parse($template);


        // Set template type
        $this->templateService->setType(TemplateService::TEMPLATE_TYPE_SINGLE);

        // Return response
        return $this->json($normalized);
    }

    public function read(int|string $id): JsonResponse
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

    public function update(int|string $id): JsonResponse
    {
        // $em          = $this->doctrine->getManager();
        $entityClass = $this->entityClass;

        // Récupère l'entité existante
        $entity = $this->repository->find($id);
        if (!$entity) {
            throw $this->createNotFoundException("Entity $entityClass with ID $id not found.");
        }

        // Récupère et décode les données
        $data = json_decode($this->requestService->getCurrentRequest()->getContent(), true) ?? [];

        // Gestion des relations
        $metadata  = $this->em->getClassMetadata($entityClass);
        $relations = [];


        foreach ($metadata->associationMappings as $field => $mapping) {
            if (!isset($data[$field])) {
                continue;
            }

            $targetEntity  = $mapping['targetEntity'];
            $relationValue = $data[$field];

            if (is_scalar($relationValue)) {
                $related = $this->em->getRepository($targetEntity)->find($relationValue);
                if ($related) {
                    $relations[$field] = $related;
                }
                unset($data[$field]);
            }
        }

        // Désérialisation (mise à jour complète)
        // $this->serializeService->denormalize($data, $entityClass, context: ['object_to_populate' => $entity]);
        // $entity = $this->serializeService->denormalize($data, $entityClass);


        foreach ($data as $field => $value) {
            $accessor = PropertyAccess::createPropertyAccessor();
            $accessor->setValue($entity, $field, $value);
        }

        // Injection des relations
        foreach ($relations as $field => $related) {
            $accessor = PropertyAccess::createPropertyAccessor();
            $accessor->setValue($entity, $field, $related);
        }

        $this->em->flush();

        // Réponse
        $normalized = $this->serializeService->normalize($entity);

        // Define template type
        $this->templateService->setType(TemplateService::TEMPLATE_TYPE_SINGLE);

        return $this->json($normalized);
    }

    public function delete(int|string $id): JsonResponse
    {
        $entity = $this->repository->find($id);
        
        if (!$entity) {
            // TODO: Not Found exception
            // throw $this->createNotFoundException("Entity $entityClass with ID $id not found.");
        }

        $this->em->remove($entity);
        $this->em->flush();

        $this->templateService->setType(TemplateService::TEMPLATE_TYPE_DELETE);

        return $this->json(['success' => true, 'deleted_id' => $id]);
    }
}