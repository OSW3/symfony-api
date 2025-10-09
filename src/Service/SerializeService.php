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
        private readonly ConfigurationService $configuration,
        private readonly SerializerInterface $serializer,
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
    ){}

    private function getContext(): array 
    {
        return [
            'provider'   => $this->configuration->guessProvider(),
            'collection' => $this->configuration->guessCollection(),
            'endpoint'   => $this->configuration->guessEndpoint(),
        ];
    }

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

    public function serialize($entity)
    {
        ['provider' => $provider,'collection' => $collection,'endpoint' => $endpoint] = $this->getContext();

        $encoder        = 'json';
        $serializer     = $this->serializer;
        $class          = $this->configuration->guessCollection();
        $groups         = $this->configuration->getSerializerGroups($provider, $collection, $endpoint);
        $ignore         = $this->configuration->getSerializerIgnore($provider, $collection, $endpoint);
        $datetimeFormat = $this->configuration->getSerializerDatetimeFormat($provider);
        $timezone       = $this->configuration->getSerializerDatetimeTimezone($provider);
        $skipNull       = $this->configuration->getSerializerSkipNull($provider);
        $hasUrlSupport  = $this->configuration->hasUrlSupport($provider);

        if (!($entity instanceof $class)) {
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
        ['provider' => $provider, 'collection' => $collection] = $this->getContext();
        
        $accessor   = PropertyAccess::createPropertyAccessor();
        $user       = $this->security->getUser();
        $isAbsolute = $this->configuration->isUrlAbsolute($provider);
        $property   = $this->configuration->getUrlProperty($provider);
        $endpoints  = array_keys($this->configuration->getEndpoints($provider, $collection) ?? []);

        foreach ($endpoints as $endpoint) 
        {
            $allowedRoles = $this->configuration->getRoles($provider, $collection, $endpoint);
            
            if (!($user === null && in_array('PUBLIC_ACCESS', $allowedRoles) || $this->security->isGranted($allowedRoles))) {
                continue;
            }

            $routeName    = $this->configuration->getEndpointRouteName($provider, $collection, $endpoint);
            $routeOptions = $this->configuration->getEndpointRouteOptions($provider, $collection, $endpoint);
            $routeMethods = $this->configuration->getEndpointRouteMethods($provider, $collection, $endpoint);
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