<?php 
namespace OSW3\Api\Controller\Crud;

use OSW3\Api\Enum\Template\Type;
use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\TemplateService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class DeleteController extends AbstractController
{
    private $em;
    private string $collection;
    private object $repository;
    private string $entityClass;

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly ContextService $contextService,
        private readonly TemplateService $templateService,
    ){
        $this->em          = $doctrine->getManager();
        $this->collection  = $contextService->getCollection();
        $this->entityClass = $this->collection;
        $this->repository  = $doctrine->getRepository($this->entityClass);
    }

    public function execute(int|string $id): JsonResponse
    {
        $entity = $this->repository->find($id);
        
        if (!$entity) {
            // TODO: Not Found exception
            // throw $this->createNotFoundException("Entity $entityClass with ID $id not found.");
        }

        $this->em->remove($entity);
        $this->em->flush();

        $this->templateService->setType(Type::DELETE->value);

        // Return response
        return $this->json(['success' => true, 'deleted_id' => $id]);
    }
}