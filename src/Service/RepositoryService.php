<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

// use Doctrine\ORM\EntityManagerInterface;

final class RepositoryService
{
    private readonly Request $request;

    public function __construct(
        // private EntityManagerInterface $entityManager,
        // private RequestService $request,
        private ConfigurationService $configuration,
        private RequestStack $requestStack,
    ){
        $this->request = $this->requestStack->getCurrentRequest();
    }


    public function execute() 
    {
        $requestMethod    = $this->getRequestMethod();
        $requestId        = $this->getRequestId();
        $provider         = $this->configuration->guessProvider();
        $collection       = $this->configuration->guessCollection();
        $endpoint         = $this->configuration->guessEndpoint();
        $repository       = $this->configuration->getRepository($provider, $collection, $endpoint);
        $repositoryMethod = $this->configuration->getMethod($provider, $collection, $endpoint);
        $repositoryCriteria = $this->configuration->getCriteria($provider, $collection, $endpoint);
        $repositoryOrderBy = $this->configuration->getOrderBy($provider, $collection, $endpoint);
        $repositoryLimit = $this->configuration->getLimit($provider, $collection, $endpoint);

        if (!method_exists($repository, $repositoryMethod)) {
            throw new \LogicException(sprintf(
                "'%s' is not a valid method for the repository '%s'.",
                $repositoryMethod,
                get_class($repository)
            ));
        }

        // $data = $requestId
        //     ? $repository->$repositoryMethod($requestId)
        //     : $repository->$repositoryMethod();



        $data = match ($repositoryMethod) {
            'find' => $repository->$repositoryMethod($requestId),
            'findAll' => $repository->$repositoryMethod(),
            'findBy' => $repository->$repositoryMethod($repositoryCriteria, $repositoryOrderBy, $repositoryLimit),

            // Request::METHOD_GET    => $id ? "find" : "findBy",
            // Request::METHOD_PUT    => "update",
            // Request::METHOD_POST   => "create",
            // Request::METHOD_PATCH  => "update",
            // Request::METHOD_DELETE => "delete",
            // default => null
        };

        dump($requestMethod);
        dump($requestId);

        dump($provider);
        dump($collection);
        dump($endpoint);

        dump($repository);
        dump($repositoryMethod);
        dump($repositoryCriteria);
        dump($repositoryOrderBy);
        dump($repositoryLimit);
        
        dump($data);
    }


    private function getRequestMethod(): string 
    {
        return $this->request->getMethod();
    }

    private function getRequestId(): ?string 
    {
        return $this->request->get('id');
    }


    // public function findAll(): array
    // {
    //     $repository = $this->getRepository();
    //     $method     = $this->request->getRepositoryMethod();
    //     $criteria   = $this->request->getRepositoryCriteria();
    //     $orderBy    = $this->request->getSorter();
    //     $total      = $repository->count($criteria);

    //     $perPage = 10;
    //     $offset = 0;

    //     // dump($total);
    //     // dump($method);
    //     // dump($criteria);
    //     // dd( $repository->findAll() );

    //     return $repository->$method(
    //         $criteria,
    //         $orderBy,
    //         $perPage,
    //         $offset
    //     );
    // }

    // public function findOne(int $id): object|null
    // {
    //     $repository = $this->getRepository();


    // }

    // public function create(): object|null
    // {

    // }

    // public function patch(int $id): object|null
    // {
    //     $repository = $this->getRepository();


    // }

    // public function delete(int $id): bool
    // {
    //     $repository = $this->getRepository();


    // }

    // private function getRepository(): object
    // {
    //     $class      = $this->request->getEntityClassname();
    //     $repository = $this->entityManager->getRepository($class);

    //     return $repository;
    // }
}