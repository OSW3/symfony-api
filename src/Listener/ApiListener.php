<?php 
namespace OSW3\SymfonyApi\Listener;

use Symfony\Component\Routing\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Loader\ClosureLoader;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Serializer\SerializerInterface;
use OSW3\SymfonyApi\DependencyInjection\Configuration;
use OSW3\SymfonyApi\Services\PaginationService;
use OSW3\SymfonyApi\Services\TseService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ApiListener implements EventSubscriberInterface
{
    private array $config;
    private null|string|int $id;
    private ?string $path;
    private Request $request;
    private int $version;
    private ?string $providerName = null;
    private array $providerData;
    private array $collectionData = [];
    private string $state = 'success';
    private $user;
    private array $methods;
    private int $code = Response::HTTP_OK;
    private array $meta = [];
    private array $error = [];
    private array $schema = [];
    private array|object $data = [];
    private array $pagination = [];


    // CONSTRUCTOR
    // --

    public function __construct(
        #[Autowire(service: 'service_container')] private ContainerInterface $container,
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private RouterInterface $router,
        private Security $security,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,

        private PaginationService $paginationService,
        private TseService $tseService,
    ){
        $this->request = $requestStack->getCurrentRequest();
        $this->config  = $this->container->getParameter(Configuration::NAME);

        $this->paginationService->setRoute('api');
    }


    // REQUEST EVENTS
    // --

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onRequest',
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if ($this->stopImmediatePropagation($event)) return;

        // Retrieve request parameters
        // --

        // Retrieve the API Version
        $version = $this->request->get('version');
        $this->version = intval($version);

        // Retrieve the URL Path
        $this->path = $this->request->get('path');

        // Retrieve the ID of the item
        $id = $this->request->get('id');
        $id = intval($id) == $id ? intval($id) : $id;
        $id = !empty($id) ? $id : null;
        $this->id = $id;


        // Current user
        // --

        $this->user = $this->security->getUser();


        // Checking Access / Security
        // --

        if (!$this->hasApiProvider() || !$this->isValidRoute())
        {
            $this->setError(Response::HTTP_NOT_FOUND);
            return;
        }

        if ($this->isApiRoute() && !$this->isGranted())
        {
            $this->setError(Response::HTTP_FORBIDDEN);
            return;
        }

        if ($this->isApiRoute() && !$this->isAllowedMethod())
        {
            $this->setError(Response::HTTP_METHOD_NOT_ALLOWED);
            return;
        }


        // CREATE THE ROUTE 
        // --

        // $routeCollection = new RouteCollection();

        // $route = new Route(
        //     path: $this->request->getPathInfo(),
        //     defaults: [],
        //     requirements: [],
        //     options: [],
        //     host: '',
        //     schemes: [],
        //     methods: $this->methods,
        //     condition: '',
        // );
        // $routeCollection->add("_api_collection", $route);

        // $loader = new ClosureLoader();
        // $loader->load(function () use ($routeCollection) {
        //     return $routeCollection;
        // });
        
        // $this->router->getRouteCollection()->addCollection($routeCollection);



        // Execute
        // --

        switch ($this->request->getMethod())
        {
            case Request::METHOD_GET:
                if ($this->isApiSearchRoute()) $this->search();
                else $this->id ? $this->findOne() : $this->findAll();
            break;
            case Request::METHOD_PUT:
                $this->id ? $this->patch() : $this->post();
            break;
            case Request::METHOD_POST:
                $this->post();
            break;
            case Request::METHOD_PATCH:
                $this->patch();
            break;
            case Request::METHOD_DELETE:
                $this->delete();
            break;
        }
    }

    public function onResponse(ResponseEvent $event): void
    {
        if ($this->stopImmediatePropagation($event)) return;


        // Response

        // Init some response data
        // --

        $this->setMetaAttribute('uri', $this->request->getPathInfo());
        $this->setMetaAttribute('api_version', 'v'.$this->version);
        $this->setMetaAttribute('provider', $this->providerName);
        $this->setMetaAttribute('status_code', $this->code);
        $this->setMetaAttribute('status_message', $this->state);
        // $this->setMetaAttribute('datetime', date('Y-m-d H:i:s'));
        $this->setMetaAttribute('execution', $this->tseService->duration());

        // Result serialization
        // --


        // $this->data is an array 
        if (gettype($this->data) === 'array')
        {
            $this->setMetaAttribute('items', count($this->data));
            array_walk($this->data, function(&$data){
                $data = $this->serialize($data);
            });
        }
        // $this->data is an object 
        else 
        {
            $this->setMetaAttribute('items', 1);
            $this->data = $this->serialize($this->data);
        }


        ksort($this->meta);
        $data['meta'] = $this->meta;

        // Response without error
        if (empty($this->error))
        {
            $pagination = $this->paginationService->response();
            if ($this->hasPagination() && !empty($pagination)) 
            {
                // $data['pagination'] = $this->pagination;
                $data['pagination'] = $pagination;
            }
            
            // $response['schema'] = $this->schema;
            $data['data'] = $this->data;
        }
        // Response with error
        else 
        {
            $data['error'] = $this->error;
        }

        // dump($response);
        // dd('API RESPONSE');

        $remove = ['X-Powered-By'];
        array_walk($remove, fn($property) => header_remove($property));

        $contentLength = strlen(json_encode($data));
        $AccessControlAllowMethods = implode(", ", $this->methods);

        $headers = [
            // 'Access-Control-Allow-Origin' => 'http://localhost:4200',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Methods' => $AccessControlAllowMethods,
            'Access-Control-Allow-Headers' => 'DNT, X-User-Token, Keep-Alive, User-Agent, X-Requested-With, If-Modified-Since, Cache-Control, Content-Type',
            'Access-Control-Max-Age' => 1728000,
            'Content-Length' => $contentLength,
        ];

        $response = new JsonResponse($data, $this->code, $headers);
        $event->setResponse($response);
    }


    // REQUESTS ACTIONS
    // --

    private function search(): void 
    {
        // Search URL param
        $search_param = $this->providerData['search']['param'];

        // Search expression
        $expression = $this->request->get($search_param);

        // Entities of search
        $entities = [];
        foreach ($this->providerData['collections'] as $collection)
        {
            foreach ($collection['privileges'] as $privilege)
            {
                if ((($this->user === null && $privilege['granted'] === null) || $this->security->isGranted($privilege['granted'])) && in_array('GET', $privilege['methods']) && !$collection['search']['excluded'] && !empty($collection['search']['criteria']))
                {
                    array_push($entities, [
                        'class' => $collection['class'],
                        'criteria' => $collection['search']['criteria']
                    ]);
                }
            }
        }

        // If expression is null = url not contain ?q
        if ($expression === null)
        {
            $this->setError(Response::HTTP_BAD_REQUEST);
            return;
        }

        // No expression = No Query !
        else if (empty($expression))
        {
            return;
        }



        // PREPARE QUERIES
        // --
        
        // Init the SQL queries array
        $queries = [];

        // Init the table alias serial (table_a as t0, table_2 as t1)
        $tableAliasSerial = 0;

        foreach ($entities as $entity)
        {
            $tableAlias = "t$tableAliasSerial";
            $where = "";
            $criteria = $entity['criteria'];

            foreach ($criteria as $columnName => $columnOption)
            {
                if (!empty($where))
                {
                    $where.= " OR ";
                }

                $where.= "$tableAlias.$columnName ";
                $where.= match($columnOption['match']) {
                    'like'           => "LIKE '%$expression%'",
                    'left-like'      => "LIKE '%$expression'",
                    'right-like'     => "LIKE '$expression%'",
                    'not-like'       => "NOT LIKE '%$expression%'",
                    'not-left-like'  => "NOT LIKE '%$expression'",
                    'not-right-like' => "NOT LIKE '$expression%'",
                    'not'            => "!= '$expression'",
                    'equal'          => "= '$expression'",
                    default          => "= '$expression'",
                };
            }
            
            if (!empty($where))
            {
                $sql = "SELECT $tableAlias ";
                $sql.= "FROM {$entity['class']} as t$tableAliasSerial ";
                $sql.= "WHERE $where";

                $queries[$entity['class']] = $sql;
            }

            $tableAliasSerial++;
        }


        // QUERIES EXECUTION
        // --

        // Init data storage
        $data = [];

        foreach ($queries as $entity => $sql)
        {
            $query = $this->entityManager->createQuery($sql);
            $results = $query->getResult();

            $data = array_merge(
                $data,
                $results
            );
        }


        // PAGINATION
        // --

        $items = count($data);
        $this->paginationService->setItems($items);

        $perPage = $this->providerData['pagination']['item_per_page'];
        $this->paginationService->setPerPage($perPage);

        $this->paginationService->execute();

        $this->paginationService->setParams([
            'version'   => $this->version,
            'path'      => $this->path,
            'page'      => $this->paginationService->getPage(),
        ]);
        $this->paginationService->setIsAbsolute(!$this->providerData['links']['absolute']);

        if ($this->paginationService->getPage() > $this->paginationService->getPages())
        {
            $this->setError(Response::HTTP_NOT_FOUND);
            return;
        }

        
        // RESULTS
        // --

        $this->data = array_slice(
            $data, 
            $this->paginationService->getOffset(), 
            $this->paginationService->getOffset()+$perPage
        );
    }

    private function findAll(): void 
    {
        $class      = $this->collectionData['class'];
        $repository = $this->entityManager->getRepository($class);
        $method     = $this->collectionData['repository_methods']['findAll'];
        $sorter     = $this->getSorter( $this->collectionData['sorter'] );
        $criteria   = $this->getCriteria( [] /*$this->collectionData['filter']*/ ); // add filter to config ex: title: xyz


        // PAGINATION
        // --

        $items = $repository->count($criteria);
        $this->paginationService->setItems($items);

        $perPage = $this->providerData['pagination']['item_per_page'];
        $this->paginationService->setPerPage($perPage);
        
        $this->paginationService->execute();

        $this->paginationService->setParams([
            'version'   => $this->version,
            'path'      => $this->path,
            'page'      => $this->paginationService->getPage(),
        ]);
        $this->paginationService->setIsAbsolute(!$this->providerData['links']['absolute']);

        if ($this->paginationService->getPage() > $this->paginationService->getPages())
        {
            $this->setError(Response::HTTP_NOT_FOUND);
            return;
        }


        // RESULTS
        // --

        $this->data = $repository->$method(
            $criteria,
            $sorter, 
            $perPage, 
            $this->paginationService->getOffset()
        );
    }

    private function findOne(): void 
    {
        $class      = $this->collectionData['class'];
        $repository = $this->entityManager->getRepository($class);
        $method     = $this->collectionData['repository_methods']['findOne'];
        $entity     = $repository->$method( $this->id );
        
        $this->data = $entity;
    }

    private function post(): void 
    {
        // New entity
        $class = $this->collectionData['class'];
        $entity = new $class;

        // POST data from the request
        $content = $this->request->getContent();
        $data = json_decode($content);
        
        // Set POST data to the entity
        $this->hydrate($entity, $data);

        // Persist & save
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

    
        $this->code = Response::HTTP_CREATED;
        $this->data = $entity;
    }

    private function patch(): void 
    {
        $class      = $this->collectionData['class'];
        $repository = $this->entityManager->getRepository($class);
        
        if ($entity = $repository->find( $this->id ))
        {
            // POST data from the request
            $content = $this->request->getContent();
            $data = json_decode($content);
            
            // Set POST data to the entity
            $this->hydrate($entity, $data);

            // Persist & save
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            $this->data = $entity;
        }
        else {
            $this->setError(Response::HTTP_NOT_FOUND);
        }
    }

    private function delete(): void 
    {
        $class      = $this->collectionData['class'];
        $repository = $this->entityManager->getRepository($class);
        
        if ($entity = $repository->find( $this->id ))
        {
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
            $this->code = Response::HTTP_NO_CONTENT;
        }
        else {
            $this->setError(Response::HTTP_NOT_FOUND);
        }
    }


    // SUPPORTS
    // --

    private function stopImmediatePropagation(RequestEvent|ResponseEvent $event): bool
    {
        if ($event instanceof RequestEvent)
        {
            return !$event->isMainRequest() && ($this->isApiRoute() || $this->isApiSearchRoute());
        }
        else 
        {
            return !($this->isApiRoute() || $this->isApiSearchRoute());
        }
    }

    /**
     * Return true if the route is "api"
     *
     * @return boolean
     */
    private function isApiRoute(): bool 
    {
        return $this->request->get('_route') === 'api';
    }

    /**
     * Return true if the route is "api_search"
     *
     * @return boolean
     */
    private function isApiSearchRoute(): bool 
    {
        return $this->request->get('_route') === 'api_search';
    }

    private function hasApiProvider(): bool 
    {
        foreach ($this->config as $providerName => $providerData)
        {
            if ($providerData['version'] === $this->version)
            {
                $this->providerName = $providerName;
                $this->providerData = $providerData;
                return true;
            }
        }

        return false;
    }

    private function hasPagination(): bool 
    {
        return $this->providerData['pagination']['state'];
    }

    private function isValidRoute(): bool 
    {
        // Search Api route + Search allowed
        if ($this->isApiSearchRoute() && $this->providerData['search']['allowed'])
        {
            return true;
        }

        // URL path is one of paths in collections
        else if ($this->isApiRoute())
        {
            $collections = $this->providerData['collections'];

            foreach ($collections as $collection)
            {
                if ($collection['paths']['singular'] === $this->path || $collection['paths']['plural'] === $this->path)
                {
                    $this->collectionData = $collection;
                    return true;
                }
            }
        }

        return false;
    }

    private function isGranted(): bool 
    {
        $privileges = $this->collectionData['privileges'];
        
        foreach ($privileges as $privilege)
        {
            if (($this->user === null && $privilege['granted'] === null) || $this->security->isGranted($privilege['granted']))
            {
                $this->methods = $privilege['methods'];
                return true;
            }
        }

        return false;
    }

    private function isAllowedMethod(): bool 
    {
        return in_array($this->request->getMethod(), $this->methods);
    }

    private function setMetaAttribute(string $name, mixed $value): self
    {
        $this->meta[$name] = $value;

        return $this;
    }

    private function setError(int $code): self
    {
        $this->state = 'failed';
        $this->code = $code;
        $this->setErrorAttribute('code', $code);
        $this->setErrorAttribute('message', match($code) {
            Response::HTTP_BAD_REQUEST => "Bad request",
            Response::HTTP_METHOD_NOT_ALLOWED => sprintf("Method %s is not allowed. Expected methods are %s", 
                $this->request->getMethod(), 
                "\"".implode('", "', $this->methods)."\"",
            ),
            Response::HTTP_NOT_FOUND => "Not Found",
            Response::HTTP_FORBIDDEN => "Forbidden access",
            Response::HTTP_INTERNAL_SERVER_ERROR => "Not acceptable"
        });

        return $this;
    }

    private function setErrorAttribute(string $name, mixed $value): self
    {
        $this->error[$name] = $value;

        return $this;
    }

    private function hydrate($entity, $data)
    {
        $reflectionClass = new \ReflectionClass(get_class($entity));
        foreach ($data as $key => $value)
        {
            if ( $this->getAssociationEntity($entity, $key) )
            {
                if ($associatedEntity = $this->getAssociationEntity($entity, $key))
                {
                    if (is_array($value))
                    {
                        foreach ($value as $id)
                        {

                            $value = $this->getEntity(
                                $associatedEntity,
                                $id
                            );
                            $this->adder($reflectionClass, $entity, $key, $value);
                        }
                    }
                    else {
                        $value = $this->getEntity(
                            $associatedEntity,
                            $value
                        );
                        $this->setter($reflectionClass, $entity, $key, $value);
                    }
                }
            }
            else 
            {
                $this->setter($reflectionClass, $entity, $key, $value);
            }
        }
    }
    private function getAssociationEntity($entity, $property): string|false
    {
        $metadata = $this->entityManager->getClassMetadata(get_class($entity));

        return $metadata->hasAssociation($property) ? $metadata->getAssociationMapping($property)['targetEntity'] : false;
    }
    private function setter($reflection, $entity, $property, $value)
    {
        $set = 'set' . ucfirst($property);
        if ($reflection->hasMethod($set)) {
            $entity->$set($value);
        }
    }
    private function adder($reflection, $entity, $property, $value)
    {
        $property = $this->singularize($property);
        $add = 'add' . ucfirst($property);
        if ($reflection->hasMethod($add)) {
            $entity->$add($value);
        }
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



    // UTILITIES
    // --

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
    private function pluralize(string $word): string
    {
        if (preg_match('/(.*[^aeiou])y$/', $word, $matches)) {
            return $matches[1] . 'ies';
        } elseif (preg_match('/(.*)(s|x|z|ch|sh)$/', $word, $matches)) {
            return $matches[0] . 'es';
        } else {
            return $word . 's'; 
        }
    }

    private function serialize($entity)
    {
        // Retrieve groups for the search
        if (gettype($entity) === 'object')
        {
            // $this->schema[] = get_class($entity);
            $groups = [];
            foreach ($this->providerData['collections'] as $collection)
            {
                if ($collection['class'] === get_class($entity))
                {
                    $groups = $collection['serializer_groups'];
                }
            }
        }
        else 
        {
            $groups = $this->collectionData['serializer_groups'];
        }

        $encoder    = 'json';
        $serializer = $this->serializer;
        $serialized = $serializer->serialize( $entity, $encoder, [ 'groups' => $groups ]);
        $serialized = json_decode($serialized, true);
        
        if ($this->providerData['links']['state'])
        {
            $this->entityLinks($serialized, $entity);
        }

        return $serialized;
    }

    private function entityLinks(&$data, $entity)
    {
        $collection_classes = [];
        foreach ($this->providerData['collections'] as $collection)
        {
            foreach ($collection['privileges'] as $privilege)
            {
                if ((($this->user === null && $privilege['granted'] === null) || $this->security->isGranted($privilege['granted'])) && in_array('GET', $privilege['methods']))
                {
                    array_push($collection_classes, $collection['class']);
                }
            }
        }

        foreach ($data as $dataKey => $dataValue) {
            $entityGetter = 'get' . ucfirst($dataKey);
    
            if (gettype($entity) === "object" && method_exists($entity::class, $entityGetter)) {
                $entityProperty = $entity->$entityGetter();
    
                if (gettype($entityProperty) === "object" && gettype($dataValue) === "array") {
                    if (is_iterable($entityProperty)) {
                        foreach ($entityProperty as $entityDataKey => $entityDataValue) {
                            if (isset($data[$dataKey][$entityDataKey])) {
                                $this->entityLinks($data[$dataKey][$entityDataKey], $entityDataValue);
                            }
                        }
                    } else {
                        $entityClass = preg_replace("/Proxies\\\__CG__\\\/", "", $entityProperty::class);

                        if (in_array($entityClass, $collection_classes))
                        {
                            $data[$dataKey]['link'] = $this->link($entityProperty);
                        }
                    }
                }
            }
        }
    
        if (gettype($entity) === "object")
        {
            $entityClass = preg_replace("/Proxies\\\__CG__\\\/", "", $entity::class);
            if (in_array($entityClass, $collection_classes))
            {
                $data['link'] = $this->link($entity);
            }
        }

    }

    private function link($entity): string 
    {
        $name = explode("\\", $entity::class);
        $name = end($name);
        $name = strtolower($name);

        return $this->urlGenerator->generate('api', [
            'version' => $this->request->get('version'),
            'path'    => $this->singularize($name),
            'id'      => $entity->getId(),
        ], !$this->providerData['links']['absolute']);
    }

    private function getSorter(array $properties = []): array
    {
        $output = [];

        if ($this->request->get('sorter'))
        {
            $properties = $this->request->get('sorter');
            $properties = explode(",", $properties);

            foreach ($properties as $key => $definition)
            {
                $definition = explode(":", $definition);
                $properties[$definition[0]] = ['order' => strtoupper($definition[1])];
                unset($properties[$key]);
            }
        }

        foreach ($properties as $name => $data)
        {
            $output[$name] = $data['order'];
        }

        return $output;
    }

    private function getCriteria(array $criteria): array
    {
        $output = [];

        foreach ($criteria as $name => $value)
        {
            $output[$name] = $value;
        }

        return $output;
    }
}