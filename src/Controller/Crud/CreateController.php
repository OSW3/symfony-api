<?php 
namespace OSW3\Api\Controller\Crud;

use OSW3\Api\Enum\Template\Type;
use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\RequestService;
use OSW3\Api\Service\TemplateService;
use OSW3\Api\Service\SerializeService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class CreateController extends AbstractController
{
    private string $collection;
    private string $entityClass;
    private $em;

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly RequestService $requestService,
        private readonly ContextService $contextService,
        private readonly TemplateService $templateService,
        private readonly SerializeService $serializeService,
    ) {
        $this->em          = $doctrine->getManager();
        $this->collection  = $contextService->getContext('collection');
        $this->entityClass = $this->collection;
    }

    public function execute(): JsonResponse
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
        $this->templateService->setType(Type::SINGLE->value);

        // Return response
        return $this->json($normalized);
    }
}