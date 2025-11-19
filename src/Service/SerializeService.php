<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\ConfigurationService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class SerializeService
{
    public function __construct(
        private readonly Security $security,
        private readonly ContextService $contextService,
        private readonly SerializerInterface $serializer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly ConfigurationService $configurationService,
    ){}

    /**
     * Get the encoder to use
     * 
     * @return string
     */
    public function getEncoder(): string
    {
        return 'json';
    }
    
    /**
     * Get the serialization groups
     * 
     * @return array
     */
    public function getGroups(): array 
    {
        return $this->configurationService->getSerializerGroups(
            provider  : $this->contextService->getProvider(),
            segment   : $this->contextService->getSegment(),
            collection: $this->contextService->getCollection(),
            endpoint  : $this->contextService->getEndpoint(),
        );
    }

    /**
     * Get the ignored attributes
     * 
     * @return array
     */
    public function getIgnoredAttributes(): array 
    {
        return $this->configurationService->getSerializerIgnore(
            provider  : $this->contextService->getProvider(),
            segment   : $this->contextService->getSegment(),
            collection: $this->contextService->getCollection(),
            endpoint  : $this->contextService->getEndpoint(),
        );
    }

    /**
     * Get the datetime format
     * 
     * @return string|null
     */
    public function getDatetimeFormat(): ?string 
    {
        return $this->configurationService->getSerializerDatetimeFormat($this->contextService->getProvider());
    }

    /**
     * Get the timezone
     * 
     * @return string|null
     */
    public function getTimezone(): ?string 
    {
        return $this->configurationService->getSerializerTimezone($this->contextService->getProvider());
    }

    /**
     * Check if null values should be skipped
     * 
     * @return bool
     */
    public function isSkipNull(): bool 
    {
        return $this->configurationService->getSerializerSkipNull($this->contextService->getProvider());
    }

    /**
     * Check if URL support is enabled
     * 
     * @return bool
     */
    public function hasUrlSupport(): bool 
    {
        return $this->configurationService->hasUrlSupport(
            provider:$this->contextService->getProvider(),
            segment: $this->contextService->getSegment(),
        );
    }

    // = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =


    public function normalize($data): array
    {
        switch (gettype($data)) {
            case 'array': 
                foreach ($data as $key => $item) {
                    $data[$key] = $this->normalize($item);
                }
                break;

            case 'object': 
                $data = $this->serialize($data);
                break;

            default: 
                $data = [];
        }

        return $data;
    }

    public function denormalize(array $data, string $entityClass)
    {
        $encoder    = $this->getEncoder();
        $serializer = $this->serializer;

        array_walk_recursive($data, function (&$value) {
            if (is_float($value)) {
                $value = (string) $value;
            }
        });

        return $serializer->deserialize( json_encode($data), $entityClass, $encoder );
    }

    public function serialize($entity)
    {
        $collection     = $this->contextService->getCollection();
        $encoder        = $this->getEncoder();
        $serializer     = $this->serializer;
        $groups         = $this->getGroups();
        $ignore         = $this->getIgnoredAttributes();
        $datetimeFormat = $this->getDatetimeFormat();
        $timezone       = $this->getTimezone();
        $skipNull       = $this->isSkipNull();
        $hasUrlSupport  = $this->hasUrlSupport();

        if (!($entity instanceof $collection)) {
            return [];
        }

        if (empty($groups)) {
            return [];
        }

        $serialized = $serializer->serialize( $entity, $encoder, [ 
            'groups'             => $groups,
            'datetime_format'    => $datetimeFormat,
            'datetime_timezone'  => $timezone,
            'ignored_attributes' => $ignore,
            'skip_null_values'   => $skipNull,
        ]);
        $serialized = json_decode($serialized, true);

        if ($hasUrlSupport)
        {
            $this->resolveUrl($serialized, $entity);
        }

        return $serialized;
    }

    private function resolveUrl(&$data, $entity)
    {
        $accessor   = PropertyAccess::createPropertyAccessor();
        $user       = $this->security->getUser();
        $provider   = $this->contextService->getProvider();
        $segment    = $this->contextService->getSegment();
        $collection = $this->contextService->getCollection();
        $isAbsolute = $this->configurationService->isUrlAbsolute($provider, $segment);
        $property   = $this->configurationService->getUrlProperty($provider, $segment);
        $endpoints  = array_keys($this->configurationService->getEndpoints($provider, $segment, $collection) ?? []);

        foreach ($endpoints as $endpoint) 
        {
            $allowedRoles = $this->configurationService->getAccessControlRoles($provider, $collection, $endpoint);

            if (!($user === null && in_array('PUBLIC_ACCESS', $allowedRoles) || $this->security->isGranted($allowedRoles))) {
                continue;
            }

            $routeName    = $this->configurationService->getRouteName($provider, $segment, $collection, $endpoint);
            $routeOptions = $this->configurationService->getRouteOptions($provider, $segment, $collection, $endpoint);
            $routeMethods = $this->configurationService->getRouteMethods($provider, $segment, $collection, $endpoint);
            $routeParams  = [];

            if (!in_array(Request::METHOD_GET, $routeMethods)) {
                continue;
            }

            if (empty($routeOptions)) {
                continue;
            }

            foreach ($routeOptions as $option) {
                $routeParams[$option] = $accessor->getValue($entity, $option);
            }
            
            $data[$property] = $this->urlGenerator->generate($routeName, $routeParams, !$isAbsolute);
        }
    }
}