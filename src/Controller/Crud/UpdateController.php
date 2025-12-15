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

final class UpdateController extends AbstractController
{
    private $em;
    private string $collection;
    private object $repository;
    private string $entityClass;

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly ContextService $contextService,
        private readonly RequestService $requestService,
        private readonly TemplateService $templateService,
        private readonly SerializeService $serializeService,
    ){
        $this->em          = $doctrine->getManager();
        $this->collection  = $contextService->getCollection();
        $this->entityClass = $this->collection;
        $this->repository  = $doctrine->getRepository($this->entityClass);
    }

    public function execute(int|string $id): JsonResponse
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
        $this->templateService->setType(Type::SINGLE->value);

        return $this->json($normalized);
    }
}