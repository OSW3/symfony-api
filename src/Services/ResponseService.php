<?php 
namespace OSW3\Api\Services;

use OSW3\Api\Services\ErrorService;
use OSW3\Api\Services\RequestService;
use OSW3\Api\Services\PaginationService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ResponseService 
{
    private readonly float $start;
    private array|object $data;

    public function __construct(
        private ConfigurationService $configuration,
        private ErrorService $errorService,
        private PaginationService $paginationService,
        private RequestService $requestService,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
        private Security $security,
    ){
        $this->start = microtime(true);
    }

    public function getContent(): array 
    {
        $content = [];

        if ($this->errorService->hasError())
        {
            // dump($this->errorService->getMessage());
            $content['message'] = $this->errorService->getMessage();
        }
        else 
        {
            // Set Meta
            $content['meta'] = $this->getMeta();

            // Set Data
            $content['data'] = $this->getData();

            // Set pagination
            if ($this->paginationService->isActive())
            {
                $content['pagination'] = $this->getPagination();
            }
        }

        return $content;
    }


    // Content Meta
    // --

    public function getMeta(): array
    {
        $meta = [];

        $meta['duration'] = $this->duration();

        return $meta;
    }


    // Content Pagination
    // --

    public function getPagination(): array
    {
        $pagination = [];

        $pagination['page']     = $this->paginationService->getPage();
        $pagination['pages']    = $this->paginationService->getPages();
        $pagination['items']    = $this->paginationService->getItems();
        $pagination['per_page'] = $this->paginationService->getPerPage();
        $pagination['urls']     = $this->paginationService->urls();

        return $pagination;
    }


    // Content Data
    // --

    public function setData(array|object $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getData(): array
    {
        $data = $this->data ?? null;

        switch (gettype($data))
        {
            case 'array':
                foreach ($data as $key => $item)
                {
                    $data[$key] = $this->serialize($item);
                }

            break;

            case 'object':
                // $data = [$data->getId()];
                $data = $this->serialize($data);
            break;

            default:
                $data = [];
        }
        
        return $data;
    }




    private function serialize($entity)
    {
        $encoder    = 'json';
        $serializer = $this->serializer;
        $groups     = $this->requestService->getSerializerGroups();
        $provider   = $this->requestService->getProvider();

        $serialized = $serializer->serialize( $entity, $encoder, [ 'groups' => $groups ]);
        $serialized = json_decode($serialized, true);
        
        if ($this->configuration->getLinkSupport($provider))
        {
            $this->entityLinks($serialized, $entity);
        }

        // dump($serialized);
        return $serialized;
    }

    private function entityLinks(&$data, $entity)
    {
        if (!is_iterable($data)) return;

        $privileges = $this->requestService->getPrivileges();
        $user = $this->security->getUser();

        $collection_classes = [];
        foreach ($privileges as $privilege)
        {
            if ((($user === null && $privilege['granted'] === null) || $this->security->isGranted($privilege['granted'])) && in_array('GET', $privilege['methods']))
            {
                array_push($collection_classes, $entity::class);
            }
        }

        foreach ($data as $key => $value) 
        {
            $getMethod = 'get' . ucfirst($key);
    
            if (gettype($entity) === "object" && method_exists($entity::class, $getMethod)) 
            {
                $property = $entity->$getMethod();
    
                if (gettype($property) === "object" && gettype($value) === "array") 
                {
                    if (is_iterable($property)) {
                        foreach ($property as $entityDataKey => $entityValue) 
                        {
                            if (isset($data[$key][$entityDataKey])) 
                            {
                                $this->entityLinks($data[$key][$entityDataKey], $entityValue);
                            }
                        }
                    } else {
                        $entityClass = preg_replace("/Proxies\\\__CG__\\\/", "", $property::class);

                        if (in_array($entityClass, $collection_classes))
                        {
                            $data[$key]['link'] = $this->link($property);
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
        $name       = explode("\\", $entity::class);
        $name       = end($name);
        $name       = strtolower($name);

        $route      = $this->requestService->getRoute();
        $route      = str_replace(":index", ":read", $route);
        $id         = $entity->getId();
        $provider   = $this->requestService->getProvider();
        $provider   = $this->requestService->getProvider();
        $isAbsolute = $this->configuration->isAbsoluteLink($provider);
        
        return $this->urlGenerator->generate($route, ['id' => $id], !$isAbsolute);
    }


    private function duration(): float
    {
        $start = $this->start;
        $end   = microtime(true);

        return $end - $start;
    }
}