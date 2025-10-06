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
    ){
        $this->request = $this->requestStack->getCurrentRequest();
    }













    

    public function getRepositoryClass(): ?string
    {
        $provider        = $this->configuration->guessProvider();
        $collection      = $this->configuration->guessCollection();
        $endpoint        = $this->configuration->guessEndpoint();
        $repositoryClass = $this->configuration->getRepositoryClass($provider, $collection, $endpoint);

        // If Repository class is not defined, fallback onto Entity repository
        if (empty($repositoryClass)) {
            $entityClass = $collection;

            if (!$entityClass || !class_exists($entityClass)) {
                return null;
            }
            
            $repositoryClass = $this->doctrine->getRepository($entityClass)::class;
        }

        return $repositoryClass;
    }

    public function getRepositoryMethod(): ?string
    {
        $provider        = $this->configuration->guessProvider();
        $collection      = $this->configuration->guessCollection();
        $endpoint        = $this->configuration->guessEndpoint();
        $method          = $this->configuration->getMethod($provider, $collection, $endpoint);

        // Resolve method if not defined
        if (!$method) {
            $method = $this->resolveRepositoryMethod();
        }

        return $method;
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
        $repository = $this->getRepositoryInstance($repositoryClass);

        return method_exists($repository, $method);
    }

    private function getRepositoryInstance(string $repositoryClass): object
    {
        if (is_subclass_of($repositoryClass, ServiceEntityRepository::class)) {
            return new $repositoryClass($this->doctrine);
        }

        return $this->doctrine->getRepository($repositoryClass);
    }

    private function resolveRepositoryMethod(): string
    {
        $httpMethod  = $this->request->getMethod();
        $routeParams = $this->request->attributes->get('_route_params', []);

        switch ($httpMethod) 
        {
            case Request::METHOD_GET:
                if (isset($routeParams['id']) && count($routeParams) > 1) {
                    return 'findOneBy';
                }
                if (isset($routeParams['id'])) {
                    return 'find';
                }
                return 'findBy'; // findAll = findBy([])

            case Request::METHOD_POST:
                return 'create';

            case Request::METHOD_PUT:
            case Request::METHOD_PATCH:
                return 'update';

            case Request::METHOD_DELETE:
                return 'remove';
        }

        throw new \RuntimeException("Aucune méthode repository définie pour {$httpMethod}");
    }




    public function resolve(): mixed
    {
        $repository   = $this->getRepository();
        $httpMethod   = $this->request->getMethod();
        $method       = $this->resolveRepositoryMethod();
        $customMethod = $this->getMethod(); // Custom method
        $criteria     = $this->getCriteria();
        $order        = $this->getOrderBy();
        $limit        = $this->getLimit();
        $offset       = $this->getOffset();
        $id           = $this->getId();
        


        // Execute Custom repository method
        if ($customMethod && !in_array($customMethod, ['find', 'findAll', 'findBy', 'findOneBy', 'count']) && method_exists($repository, $customMethod)) {
            // $params = [];
            // $params = array_merge($params, $this->request->attributes->get('_route_params', []));
            // $params = array_merge($params, $this->request->query->all());
            
            $params = array_merge(
                $this->request->query->all(),
                $this->request->request->all(),
                $this->request->attributes->all() // paramètres de route (_route_params)
            );

            // TODO: check allowed parameters (from route.options ?)
            // $provider   = $this->configuration->guessProvider();
            // $collection = $this->configuration->guessCollection();
            // $endpoint   = $this->configuration->guessEndpoint();
            // $routeOptions = $this->configuration->getEndpointRouteOptions($provider,$collection,$endpoint);

            return $repository->$customMethod($params);
            // dump($repository->$customMethod($params));
            // dd("CUSTOM METHOD {$method}");
        }

        $data = match ($httpMethod) 
        {
            Request::METHOD_GET    => $this->handleGet($id, $criteria, $order, $limit, $offset),
            Request::METHOD_POST   => $this->create(),
            Request::METHOD_PUT    => $this->update($id),
            Request::METHOD_PATCH  => $this->update($id),
            Request::METHOD_DELETE => $this->delete($id),
            default => throw new \LogicException("Unsupported HTTP method: $customMethod")
        };

        return $data;

        // dump($customMethod, $method);
        // dump($data);
        // dd('---');


        // return match ($httpMethod) 
        // {
        //     Request::METHOD_GET    => $this->handleGet($id, $criteria, $order, $limit, $offset),
        //     Request::METHOD_POST   => $this->create(),
        //     Request::METHOD_PUT    => $this->update($id),
        //     Request::METHOD_PATCH  => $this->update($id),
        //     Request::METHOD_DELETE => $this->delete($id),
        //     default => throw new \LogicException("Unsupported HTTP method: $customMethod")
        // };
    }
    private function handleGet(?int $id, array $criteria, array $order, ?int $limit, ?int $offset)
    {
        if ($id && !empty($criteria)) {
            return $this->findOneBy($criteria);
        }

        if ($id) {
            return $this->find($id);
        }

        return $this->findBy($criteria, $order, $limit, $offset);
    }



    /**
     * Get the repository
     */
    private function getRepository(): object
    {
        $provider   = $this->configuration->guessProvider();
        $collection = $this->configuration->guessCollection();
        $endpoint   = $this->configuration->guessEndpoint();

        return $this->configuration->getRepository($provider, $collection, $endpoint);
    }

    /**
     * Get the repository method
     */
    private function getMethod(): ?string
    {
        $provider   = $this->configuration->guessProvider();
        $collection = $this->configuration->guessCollection();
        $endpoint   = $this->configuration->guessEndpoint();

        return $this->configuration->getMethod($provider, $collection, $endpoint);
    }

    /**
     * Get the repository criteria
     */
    private function getCriteria(): array
    {
        $provider   = $this->configuration->guessProvider();
        $collection = $this->configuration->guessCollection();
        $endpoint   = $this->configuration->guessEndpoint();

        return $this->configuration->getCriteria($provider, $collection, $endpoint);
    }

    /**
     * Get the repository Order By
     */
    private function getOrderBy(): array
    {
        $provider   = $this->configuration->guessProvider();
        $collection = $this->configuration->guessCollection();
        $endpoint   = $this->configuration->guessEndpoint();

        return $this->configuration->getOrderBy($provider, $collection, $endpoint);
    }

    /**
     * Get the repository Limit
     */
    private function getLimit(): ?int
    {
        $provider   = $this->configuration->guessProvider();
        $collection = $this->configuration->guessCollection();
        $endpoint   = $this->configuration->guessEndpoint();

        return $this->configuration->getLimit($provider, $collection, $endpoint);
    }

    private function getOffset(): ?int
    {
        return null;
    }

    private function getId(): ?string 
    {
        return $this->request->get('id');
    }




    private function findBy(array $criteria, array $order, ?int $limit, ?int $offset): array
    {
        $repository = $this->getRepository();
        $entities   = $repository->findBy($criteria, $order, $limit, $offset);

        return $entities;
    }

    private function find(int|string $id): object|null
    {
        $repository = $this->getRepository();
        $entity     = $repository->find($id);

        return $entity;
    }

    private function findOneBy(array $criteria): object|null
    {
        $repository = $this->getRepository();
        $entity     = $repository->findOneBy($criteria);

        return $entity;
    }

    private function create(): object|null
    {
        $data       = $this->request->getContent();
        $data       = json_decode($data);
        $collection = $this->configuration->guessCollection();
        $entity     = new $collection;

        if (!$this->hydrate($entity, $data))
        {
            return null;
        }
        
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        
        return $entity;
    }

    private function update(int|string $id): object|null
    {
        $repository = $this->getRepository();
        if ($entity = $repository->find($id))
        {
            $data = $this->request->getContent();
            $data = json_decode($data);

            if (!$this->hydrate($entity, $data))
            {
                return null;
            }

            $this->entityManager->flush();
        }

        return $entity;
    }

    private function delete(int|string $id): bool
    {
        $repository = $this->getRepository();
        if ($entity = $repository->find($id))
        {
            $this->entityManager->remove($entity);
            $this->entityManager->flush();

            return true;
        }

        return false;
    }



    private function hydrate($entity, $data): bool
    {
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
                            $value = $this->getEntity(
                                $associatedEntity,
                                $id
                            );
                            $isValidProperty = $this->adder($reflectionClass, $entity, $property, $value);

                            if (!$isValidProperty) break;
                        }
                    }
                    else {
                        $value = $this->getEntity(
                            $associatedEntity,
                            $value
                        );
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
        $set = 'set' . ucfirst($property);
        if (!$reflection->hasMethod($set))
        {
            return false;
        }

        $entity->$set($value);
        return true;
        
    }
    private function adder($reflection, $entity, $property, $value): bool
    {
        $property = $this->singularize($property);
        $add = 'add' . ucfirst($property);
        if (!$reflection->hasMethod($add)) 
        {
            return false;
        }

        $entity->$add($value);
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

}