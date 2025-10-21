<?php 
namespace OSW3\Api\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

final class RepositoryService
{
    private readonly Request $request;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ConfigurationService $configuration,
        private readonly ManagerRegistry $doctrine,
        private readonly RequestStack $requestStack,
        private readonly UtilsService $utilsService,
        private readonly RequestService $requestService,
        private readonly ResponseService $responseService,
        private readonly ResponseStatusService $responseStatusService,
        private readonly PaginationService $pagination,
    ){
        $this->request = $requestStack->getCurrentRequest();
    }

    private function getContext(): array 
    {
        return [
            'provider'   => $this->configuration->guessProvider(),
            'collection' => $this->configuration->guessCollection(),
            'endpoint'   => $this->configuration->guessEndpoint(),
        ];
    }
    
    private function getRepository(): object
    {
        ['provider' => $provider,'collection' => $collection,'endpoint' => $endpoint] = $this->getContext();
        return $this->configuration->getRepository($provider, $collection, $endpoint);
    }

    private function getOffset(): int
    {
        return 0;
    }


    // ──────────────────────────────
    // Resolvers
    // ──────────────────────────────

    /**
     * Define the repository class to use
     * Try to find custom class (repository.service), fallback to the entity (collection name) repository
     */
    public function resolveRepositoryClass(): ?string
    {
        $context = $this->getContext();
        $provider = $context['provider'];
        $entityClass = $context['collection'];
        $endpoint = $context['endpoint'];
        // dd($context);
        

        $repositoryClass = $this->configuration->getRepositoryClass($provider, $entityClass, $endpoint);

        // If Repository class is not defined, fallback onto Entity repository
        if (empty($repositoryClass)) {
            if (!$entityClass || !class_exists($entityClass)) {
                return null;
            }
            
            $repositoryClass = $this->doctrine->getRepository($entityClass)::class;
        }

        return $repositoryClass;
    }

    public function resolveRepositoryMethod(): ?string
    {
        [
            'provider'   => $provider,
            'collection' => $collection,
            'endpoint'   => $endpoint
        ] = $this->getContext();

        // Try to get  the custom method from the config (repository.method)
        $method = $this->configuration->getMethod($provider, $collection, $endpoint);

        // Resolve method if not defined
        if (!$method) 
        {
            $httpMethod  = $this->request->getMethod();
            $routeParams = $this->request->attributes->get('_route_params', []);

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

    public function resolveRepositoryInstance(string $repositoryClassOrEntity): object
    {
        // Si c’est une entité connue par Doctrine, retourne son repository standard
        if (class_exists($repositoryClassOrEntity) && $this->doctrine->getManagerForClass($repositoryClassOrEntity)) {
            return $this->doctrine->getRepository($repositoryClassOrEntity);
        }

        // Si c’est un repository custom héritant de ServiceEntityRepository, instancie avec Doctrine
        if (class_exists($repositoryClassOrEntity) && is_subclass_of($repositoryClassOrEntity, ServiceEntityRepository::class)) {
            return new $repositoryClassOrEntity($this->doctrine);
        }

        throw new \RuntimeException(
            sprintf('Impossible de récupérer une instance pour "%s". Vérifie que c’est une entité ou un repository valide.', $repositoryClassOrEntity)
        );
    }

    public function isRepositoryCallable(): bool 
    {
        $repositoryClass = $this->resolveRepositoryClass();
        $method          = $this->resolveRepositoryMethod();

        // dd($repositoryClass, $method);

        // If Repository class is not defined, fallback onto Entity repository
        if (empty($repositoryClass)) {
            return false;
        }

        // Is the repository class exists
        if (!class_exists($repositoryClass)) {
            return false;
        }
        $repository = $this->resolveRepositoryInstance($repositoryClass);

        if ($this->isStandardHttpMethod($method)) {
            return true;
        }

        return method_exists($repository, $method);
    }

    private function isStandardHttpMethod(string $method): bool
    {
        return in_array($method, ['create', 'update', 'delete', 'find', 'findOneBy', 'findBy', 'count'], true);
    }


    // ──────────────────────────────
    // Executor
    // ──────────────────────────────

    private function findBy(array $criteria, array $order, ?int $limit, ?int $offset): array
    {
        $this->pagination->enable();
        $this->responseStatusService->setCode(200);
        return $this->getRepository()->findBy($criteria, $order, $limit, $offset);
    }

    private function find(int|string $id): object|null
    {
        $this->responseStatusService->setCode(200);
        return $this->getRepository()->find($id);
    }

    private function findOneBy(array $criteria): object|null
    {
        $this->responseStatusService->setCode(200);
        return $this->getRepository()->findOneBy($criteria);
    }

    private function count(array $criteria): int
    {
        return $this->getRepository()->count($criteria);
    }

    private function create(): object|null
    {
        $data       = $this->request->getContent();
        $data       = json_decode($data, true) ?? [];
        $collection = $this->configuration->guessCollection();
        $entity     = new $collection;

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

    private function hydrate($entity,  $data): bool
    {
        // dump(get_debug_type($data));
        // dd($data); 

        $isValidProperty = true;

        $reflectionClass = new \ReflectionClass(get_class($entity));
        foreach ($data as $property => $value)
        {
            if ( $this->getAssociationEntity($entity, $property) )
            {
                if ($associatedEntity = $this->getAssociationEntity($entity, $property))
                {
                    if (is_array($value))
                    {
                        foreach ($value as $id)
                        {
                            $value = $this->getEntity($associatedEntity,$id);
                            $isValidProperty = $this->adder($reflectionClass, $entity, $property, $value);

                            if (!$isValidProperty) break;
                        }
                    }
                    else {
                        $value = $this->getEntity($associatedEntity,$value);
                        $isValidProperty = $this->setter($reflectionClass, $entity, $property, $value);
                    }
                }
            }
            else 
            {
                $isValidProperty = $this->setter($reflectionClass, $entity, $property, $value);
            }

            if (!$isValidProperty) break;
        }

        return $isValidProperty;
    }
    private function getAssociationEntity($entity, $property): string|false
    {
        $metadata = $this->entityManager->getClassMetadata(get_class($entity));

        return $metadata->hasAssociation($property) ? $metadata->getAssociationMapping($property)['targetEntity'] : false;
    }
    private function setter($reflection, $entity, $property, $value): bool
    {
        $method = 'set' . $this->utilsService->camelize($property);

        if (!$reflection->hasMethod($method))
        {
            return false;
        }

        $entity->$method($value);
        return true;
        
    }
    private function adder($reflection, $entity, $property, $value): bool
    {
        $property = $this->singularize($property);
        $method = 'add' . $this->utilsService->camelize($property);
        if (!$reflection->hasMethod($method)) 
        {
            return false;
        }

        $entity->$method($value);
        return true;
    }
    /**
     * Find the associated entity
     *
     * @param [type] $entity
     * @param [type] $id
     * @return void
     */
    private function getEntity($entity, $id)
    {
        return $this->entityManager->getRepository($entity)->find($id);
    }
    private function singularize(string $word): string
    {
        if (preg_match('/(.*[^aeiou])ies$/', $word, $matches)) {
            return $matches[1] . 'y';
        } elseif (preg_match('/(.*)(ses|xes|zes|ches|shes)$/', $word, $matches)) {
            return $matches[1]; 
        } elseif (preg_match('/(.*)s$/', $word, $matches)) {
            return $matches[1]; 
        } else {
            return $word;
        }
    }




    public function execute(): mixed
    {
        ['provider' => $provider, 'collection' => $collection, 'endpoint' => $endpoint] = $this->getContext();

        // Résolution repository
        $repositoryClass = $this->resolveRepositoryClass();
        if (!$repositoryClass) {
            throw new \RuntimeException("Aucun repository trouvé pour {$collection}");
        }
        $repository = $this->resolveRepositoryInstance($repositoryClass);

        // Résolution méthode
        $customMethod = $this->configuration->getMethod($provider, $collection, $endpoint);
        $method = $customMethod ?? $this->resolveRepositoryMethod();

        // Paramètres pour méthode custom
        $params = $customMethod ? $this->requestService->getParams() : [];

        // ──────────────
        // Execution
        // ──────────────
        if ($customMethod) {
            // Si la méthode existe et est callable
            if (!is_callable([$repository, $customMethod])) {
                throw new \RuntimeException(
                    sprintf('Méthode custom "%s" introuvable dans "%s"', $customMethod, $repositoryClass)
                );
            }
            return $repository->$customMethod(...array_values($params));
        }

        // Méthodes standard HTTP
        $id       = $this->request->get('id');
        $criteria = $this->configuration->getCriteria($provider, $collection, $endpoint);
        $order    = $this->configuration->getOrderBy($provider, $collection, $endpoint);
        $limit    = $this->configuration->getLimit($provider, $collection, $endpoint);
        $offset   = $this->getOffset();

        if ($id) {
            $criteria = ['id' => $id];
        }

        $this->pagination->setTotal( $this->count($criteria) );


        // dd($id, $method, $criteria);
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