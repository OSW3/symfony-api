<?php 
namespace OSW3\Api\Service;

use Doctrine\ORM\EntityManagerInterface;

final class RepositoryService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestService $request,
    ){}

    public function findAll(): array
    {
        $repository = $this->getRepository();
        $method     = $this->request->getRepositoryMethod();
        $criteria   = $this->request->getRepositoryCriteria();
        $orderBy    = $this->request->getSorter();
        $total      = $repository->count($criteria);

        $perPage = 10;
        $offset = 0;

        // dump($total);
        // dump($method);
        // dump($criteria);
        // dd( $repository->findAll() );

        return $repository->$method(
            $criteria,
            $orderBy,
            $perPage,
            $offset
        );
    }

    public function findOne(int $id): object|null
    {
        $repository = $this->getRepository();


    }

    public function create(): object|null
    {

    }

    public function patch(int $id): object|null
    {
        $repository = $this->getRepository();


    }

    public function delete(int $id): bool
    {
        $repository = $this->getRepository();


    }

    private function getRepository(): object
    {
        $class      = $this->request->getEntityClassname();
        $repository = $this->entityManager->getRepository($class);

        return $repository;
    }
}