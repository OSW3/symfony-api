<?php
namespace OSW3\Api\Services;

use OSW3\Api\Services\RequestService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class RepositoryService 
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestService $requestService,
        private HeadersService $headersService,
        private ResponseService $responseService,
        private PaginationService $paginationService,
        private RequestStack $requestStack,
    ){}

    public function findAll(): array
    {
        $repository = $this->getRepository();
        $method     = $this->requestService->getMethod('findAll');
        $criteria   = [];
        $orderBy    = $this->requestService->getSorter();
        $total      = $repository->count($criteria);

        // Pagination
        $this->paginationService->setStatus(true);
        $this->paginationService->setPages($total);

        $perPage    = $this->paginationService->getPerPage();
        $offset     = $this->paginationService->getOffset();
        $results    = $repository->$method(
            $criteria,
            $orderBy, 
            $perPage, 
            $offset
        );
        
        return $results;
    }

    public function findOne(int $id): object|null
    {
        $repository = $this->getRepository();
        $method     = $this->requestService->getMethod('find');
        $result     = $repository->$method($id);

        return $result;
    }

    public function create(): object|null
    {
        $request = $this->requestStack->getCurrentRequest();
        $class   = $this->requestService->getClass();
        $data    = json_decode($request->getContent());
        $entity  = new $class;
        
        // Set POST data to the entity
        if (!$this->hydrate($entity, $data))
        {
            return null;
        }
        
        // Persist & save
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        
        return $entity;
    }

    public function patch(int $id): object|null
    {
        $repository = $this->getRepository();
        
        if ($entity = $repository->find($id))
        {
            $request = $this->requestStack->getCurrentRequest();
            $data    = json_decode($request->getContent());
            
            // Set POST data to the entity
            if (!$this->hydrate($entity, $data))
            {
                return null;
            }

            // Persist & save
            $this->entityManager->persist($entity);
            $this->entityManager->flush();
        }

        return $entity;
    }

    public function delete(int $id): bool
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

    /**
     * Get the repository
     */
    private function getRepository()
    {
        $class      = $this->requestService->getClass();
        $repository = $this->entityManager->getRepository($class);

        return $repository;
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