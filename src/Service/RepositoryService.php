<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ContextService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

final class RepositoryService
{
    private readonly Request $request;

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly RequestStack $requestStack,
        private readonly RequestService $requestService,
        private readonly ContextService $contextService,
        private readonly ConfigurationService $configuration,
        private readonly PaginationService $paginationService,
        private readonly EntityManagerInterface $entityManager,
        private readonly ResponseStatusService $responseStatusService,
    ){
        $this->request = $requestStack->getCurrentRequest();
    }

    private function getId(): int|string|null
    {
        return $this->request->get('id');
    }

    private function getCriteria(): array
    {
        return $this->configuration->getCriteria(
            provider  : $this->contextService->getProvider(),
            segment   : $this->contextService->getSegment(),
            collection: $this->contextService->getCollection(),
            endpoint  : $this->contextService->getEndpoint(),
        );
    }

    private function getOrderBy(): array
    {
        return $this->configuration->getOrderBy(
            provider  : $this->contextService->getProvider(),
            segment   : $this->contextService->getSegment(),
            collection: $this->contextService->getCollection(),
            endpoint  : $this->contextService->getEndpoint(),
        );
    }

    private function getLimit(): int
    {
        // $limit = $this->configuration->getLimit(
        //     provider  : $this->contextService->getProvider(),
        //     collection: $this->contextService->getCollection(),
        //     endpoint  : $this->contextService->getEndpoint(),
        // );

        // if ($this->paginationService->isEnabled()) {
        //     $this->paginationService->getLimit();
        // }

        if (!$this->paginationService->isEnabled()) {
            return 0;
        }

        return $this->paginationService->getLimit();
    }

    private function getOffset(): ?int
    {
        return $this->paginationService->getOffset();
    }


    // ──────────────────────────────
    // Class and Method Resolvers
    // ──────────────────────────────

    public function getRepositoryClass(): ?string
    {
        return $this->resolveRepositoryClass();
    }

    public function getRepositoryMethod(): ?string
    {
        return $this->resolveRepositoryMethod();
    }

    public function getRepositoryInstance(): ?object
    {
        $repositoryClass = $this->getRepositoryClass();

        if (!$repositoryClass) {
            return null;
        }

        return $this->resolveRepositoryInstance($repositoryClass);
    }


    // ──────────────────────────────
    // Resolvers
    // ──────────────────────────────

    /**
     * Define the repository class to use
     * Try to find custom class (repository.service), fallback to the entity (collection name) repository
     */
    private function resolveRepositoryClass(): ?string
    {
        // Get the entity class from the context
        $entityClass = $this->contextService->getCollection();

        // Get the repository class from the configuration
        $repositoryClass = $this->configuration->getRepositoryClass(
            provider  : $this->contextService->getProvider(),
            segment   : $this->contextService->getSegment(),
            collection: $this->contextService->getCollection(),
            endpoint  : $this->contextService->getEndpoint(),
        );

        // If Repository class is not defined, fallback onto Entity repository
        if (empty($repositoryClass)) {
            if (!$entityClass || !class_exists($entityClass)) {
                return null;
            }
            
            $repositoryClass = $this->doctrine->getRepository($entityClass)::class;
        }

        return $repositoryClass;
    }

    private function resolveRepositoryMethod(): ?string
    {
        // Output
        $method = null; 

        // Get allowed HTTP methods for the route
        $repositoryMethod = $this->configuration->getRepositoryMethod(
            provider  : $this->contextService->getProvider(),
            segment   : $this->contextService->getSegment(),
            collection: $this->contextService->getCollection(),
            endpoint  : $this->contextService->getEndpoint(),
        );

        // Get HTTP method
        $httpMethod  = $this->request->getMethod();

        // Get route parameters
        $routeParams = $this->request->attributes->get('_route_params', []);

        // Resolve method if not defined
        if (!$repositoryMethod) 
        {
            switch ($httpMethod) 
            {
                case Request::METHOD_GET:
                    if (isset($routeParams['id']) && count($routeParams) > 1) {
                        $method = 'findOneBy';
                        break;
                    }
                    $method = isset($routeParams['id']) ? 'find' : 'findBy';
                break;


                case Request::METHOD_POST:
                    $method = 'create';
                break;

                case Request::METHOD_PUT:
                case Request::METHOD_PATCH:
                    $method = 'update';
                break;

                case Request::METHOD_DELETE:
                    $method = 'delete';
                break;
            }
        }

        return $method;
    }

    private function resolveRepositoryInstance(string $repositoryClassOrEntity): object
    {
        // Si c’est une entité connue par Doctrine, retourne son repository standard
        if (class_exists($repositoryClassOrEntity) 
            && $this->doctrine->getManagerForClass($repositoryClassOrEntity)){
            return $this->doctrine->getRepository($repositoryClassOrEntity);
        }

        // Si c’est un repository custom héritant de ServiceEntityRepository, instancie avec Doctrine
        if (
            class_exists($repositoryClassOrEntity) 
            && is_subclass_of($repositoryClassOrEntity, ServiceEntityRepository::class)
        ){
            return new $repositoryClassOrEntity($this->doctrine);
        }

        throw new \RuntimeException(
            sprintf('Impossible de récupérer une instance pour "%s". Vérifie que c’est une entité ou un repository valide.', $repositoryClassOrEntity)
        );
    }

    public function isRepositoryCallable(): bool 
    {
        $repositoryClass = $this->getRepositoryClass();
        $method          = $this->getRepositoryMethod();

        // If Repository class is not defined, fallback onto Entity repository
        if (empty($repositoryClass)) {
            return false;
        }

        // Is the repository class exists
        if (!class_exists($repositoryClass)) {
            return false;
        }
        $repository = $this->resolveRepositoryInstance($repositoryClass);

        return method_exists($repository, $method);
    }


    // ──────────────────────────────
    // Executor
    // ──────────────────────────────

    private function findBy(array $criteria, array $order, ?int $limit, ?int $offset): array
    {
        $this->paginationService->isEnabled();
        $this->responseStatusService->setCode(200);

        // $repositoryClass = $this->resolveRepositoryClass();
        // $repository = $this->resolveRepositoryInstance($repositoryClass);

        $repository = $this->getRepositoryInstance();
        return $repository->findBy($criteria, $order, $limit, $offset);
    }

    private function find(int|string $id): object|null
    {
        $this->responseStatusService->setCode(200);
        return $this->getRepository()->find($id);
    }

    private function findOneBy(array $criteria): object|null
    {
        if (!$result = $this->getRepository()->findOneBy($criteria))
        {
            $this->responseStatusService->setCode(404);
            return null;
        }


        $this->responseStatusService->setCode(200);
        return $result;
    }

    private function count(array $criteria): int
    {
        $repositoryClass = $this->resolveRepositoryClass();
        $repository = $this->resolveRepositoryInstance($repositoryClass);
        return $repository->count($criteria);
    }

    private function create(): object|null
    {
        $data       = $this->request->getContent();
        $data       = json_decode($data, true) ?? [];
        $collection = $this->configuration->getContext('collection');
        $entity     = new $collection;


        // $this->hydrate($entity, $data);
        // dd($entity);


        if (!$this->hydrate($entity, $data))
        {
            return null;
        }
        
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        
        $this->responseStatusService->setCode(201);
        return $entity;
    }

    private function update(int|string $id): object|null
    {
        if ($entity = $this->getRepository()->find($id))
        {
            $data = $this->request->getContent();
            $data = json_decode($data, true) ?? [];

            if (!$this->hydrate($entity, $data))
            {
                return null;
            }

            $this->entityManager->flush();
        }

        $this->responseStatusService->setCode(200);
        return $entity;
    }

    private function delete(int|string $id): bool
    {
        if ($entity = $this->getRepository()->find($id))
        {
            $this->entityManager->remove($entity);
            $this->entityManager->flush();

            $this->responseStatusService->setCode(200);
            return true;
        }


        $this->responseStatusService->setCode(404);
        return false;
    }



    // ──────────────────────────────
    // Hydrator
    // ──────────────────────────────

    private function hydrate(object $entity, array $data): bool
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $metadata = $this->entityManager->getClassMetadata($entity::class);

        foreach ($data as $property => $value) {
            // Has Doctrine relation ?
            if ($metadata->hasAssociation($property)) {
                $targetEntity = $metadata->getAssociationTargetClass($property);

                if (is_array($value)) {
                    // Relations ManyToMany
                    foreach ($value as $id) {
                        $related = $this->entityManager->getReference($targetEntity, $id);
                        $adder = 'add' . ucfirst(rtrim($property, 's'));
                        if (method_exists($entity, $adder)) {
                            $entity->$adder($related);
                        }
                    }
                } else {
                    // Relations ManyToOne, OneToOne
                    $related = $this->entityManager->getReference($targetEntity, $value);
                    $accessor->setValue($entity, $property, $related);
                }
            } 
            // Simple property
            else {
                $accessor->setValue($entity, $property, $value);
            }
        }

        return true;
    }





    public function execute(): mixed
    {
        // Get repository class
        $repositoryClass = $this->resolveRepositoryClass();
        $repository = $this->resolveRepositoryInstance($repositoryClass);

        // Get repository method
        $repositoryMethod = $this->configuration->getRepositoryMethod(
            provider  : $this->contextService->getProvider(),
            segment   : $this->contextService->getSegment(),
            collection: $this->contextService->getCollection(),
            endpoint  : $this->contextService->getEndpoint(),
        );
        $method = $repositoryMethod ?? $this->resolveRepositoryMethod();

        // Get params if custom method
        $params = $repositoryMethod ? $this->requestService->getParameters() : [];

        if ($repositoryMethod) {
            // Si la méthode existe et est callable
            if (!is_callable([$repository, $repositoryMethod])) {
                throw new \RuntimeException(
                    sprintf('Méthode custom "%s" introuvable dans "%s"', $repositoryMethod, $repositoryClass)
                );
            }
            return $repository->$repositoryMethod(...array_values($params));
        }


        $id       = $this->getId();
        $criteria = $this->getCriteria();
        $order    = $this->getOrderBy();
        $limit    = $this->getLimit();
        $offset   = $this->getOffset();

        if ($id) {
            $criteria = ['id' => $id];
        }

        $this->paginationService->setTotal( $this->count($criteria) );


        return match ($method) {
            'find'       => $this->find($id),
            'findOneBy'  => $this->findOneBy($criteria),
            'findBy'     => $this->findBy($criteria, $order, $limit, $offset),
            'findAll'    => $this->findBy([], [], null, null),
            'count'      => $this->count($criteria),
            'create'     => $this->create(),
            'update'     => $this->update($id),
            'delete'     => $this->delete($id),
            default      => throw new \LogicException("Méthode non supportée: {$method}"),
        };
    }

    // TODO: is Not found

}