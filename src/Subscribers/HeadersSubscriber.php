<?php
namespace OSW3\Api\Subscribers;

use OSW3\Api\Helper\HeaderHelper;
use OSW3\Api\Builder\OptionsBuilder;
use OSW3\Api\Service\HeadersService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class HeadersSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly HeadersService $headersService,
        private readonly OptionsBuilder $optionsBuilder,
    ){}
    
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onResponse', -1000],
        ];
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // Get current response
        $response = $event->getResponse();

        // Get headers directives
        $exposed = $this->headersService->getExposedDirectives();

        // Get removed headers
        $removed = [];

        // Exposed headers
        foreach ($exposed as $key => $value) 
        {
            $key    = HeaderHelper::toHeaderCase($key);
            $value  = $this->resolveHeaderValue($key, $value, $event);
            
            if ($this->shouldRemoveHeader($value)) {
                $removed[] = $key;
                continue;
            }

            $response->headers->set($key, $value);
        }

        // Remove headers
        foreach ($removed as $key) 
        {
            $key = HeaderHelper::toHeaderCase($key);
            $response->headers->remove($key);
        }

        // Strip X Prefix
        foreach ($response->headers->all() as $key => $value) {
            $newKey = $key;

            if ($this->headersService->stripXPrefix() &&  str_starts_with(strtolower($newKey), 'x-')) 
            {
                $newKey = substr($key, 2);
                $response->headers->remove($key);
                $response->headers->set(ucwords($newKey, '-'), $value);
            }

            if ($this->headersService->keepLegacy() && $key !== $newKey) {
                $response->headers->set(ucwords($key, '-'), $value);
            }
        }
    }


    private function resolveHeaderValue(string $key, mixed $value, ResponseEvent $event): mixed
    {
        if ($value === true && $event->getResponse()->headers->has($key)) {
            return $event->getResponse()->headers->get($key);
        }

        if ($value === false) {
            return null;
        }

        $response = $event->getResponse();

        $this->optionsBuilder->setContext('headers');

        return $this->optionsBuilder->build($key, $value,[
                'response' => $response,
            ]) 
            ?? $this->resolveDynamicValue($value, $response) 
            ?? $value
        ;
        return null;
    }

    private function resolveDynamicValue(mixed $value, Response $response): mixed
    {
        if (!is_string($value) && !is_callable($value)) {
            return $value;
        }

        if (is_string($value) && str_contains($value, '::')) {
            [$class, $method] = explode('::', $value);
            $reflection = new \ReflectionMethod($class, $method);
            
            if ($reflection->isStatic()) {
                return call_user_func($value, $response);
            } else {
                // dump(['class' => $class, 'method' => $method]);
                return call_user_func([new $class(), $method], $response);
            }
        }

        if (is_callable($value)) {
            return $value($response);
        }

        return $value;
    }

    private function shouldRemoveHeader(mixed $value): bool
    {
        return is_bool($value) || $value === null || $value === '';
    }
}