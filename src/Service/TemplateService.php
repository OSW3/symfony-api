<?php 
namespace OSW3\Api\Service;

use Symfony\Component\Yaml\Yaml;
use OSW3\Api\Builder\OptionsBuilder;
use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\EndpointService;
use OSW3\Api\Service\ProviderService;
use OSW3\Api\Service\ResponseService;
use Symfony\Component\Filesystem\Path;
use OSW3\Api\Service\CollectionService;
use Symfony\Component\HttpFoundation\Response;
use OSW3\Api\DependencyInjection\Configuration;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class TemplateService 
{
    private readonly array $configuration;
    private string $type;

    public function __construct(
        #[Autowire(service: 'service_container')] 
        private readonly ContainerInterface $container,
        private readonly KernelInterface $kernel,
        private readonly RouteService $routeService,
        private readonly OptionsBuilder $optionsBuilder,
        private readonly ContextService $contextService,
        private readonly ProviderService $providerService,
        private readonly EndpointService $endpointService,
        private readonly ResponseService $responseService,
        private readonly CollectionService $collectionService,
    ){
        $this->configuration = $container->getParameter(Configuration::NAME);
    }

    /**
     * Get the template options for the given provider/segment/collection/endpoint
     * 
     * @param string $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return array
     */
    private function options(string $provider, ?string $segment, ?string $collection = null, ?string $endpoint = null): array
    {
        if (! $this->providerService->exists($provider)) {
            return $this->configuration['templates'];
        }

        // 1. Endpoint-specific templates
        if ($collection && $endpoint) {
            $endpointOptions = $this->endpointService->get($provider, $segment, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['templates'])) {
                return $endpointOptions['templates'] ?? [];
            }
        }

        // 2. Collection-level templates
        if ($collection) {
            $collectionOptions = $this->collectionService->get($provider, $segment, $collection);
            if ($collectionOptions && isset($collectionOptions['templates'])) {
                return $collectionOptions['templates'] ?? [];
            }
        }

        // 3. Global default templates
        $providerOptions = $this->providerService->get($provider);
        return $providerOptions['templates'] ?? [];
    }


    // -- CONFIG OPTIONS GETTERS

    /**
     * Get all template options for the given provider/segment/collection/endpoint
     * 
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return array
     */
    public function all(?string $provider = null, ?string $segment = null, ?string $collection = null, ?string $endpoint = null, bool $fallbackOnCurrentContext = true): array 
    {
        if ($fallbackOnCurrentContext) {
            $provider   ??= $this->contextService->getProvider();
            $segment    ??= $this->contextService->getSegment();
            $collection ??= $this->contextService->getCollection();
            $endpoint   ??= $this->contextService->getEndpoint();
        }

        return $this->options(
            provider  : $provider,
            segment   : $segment,
            collection: $collection,
            endpoint  : $endpoint
        );
    }

    /**
     * Get a specific template option for the given provider/segment/collection/endpoint
     * 
     * @param string $type
     * @param string|null $provider
     * @param string|null $segment
     * @param string|null $collection
     * @param string|null $endpoint
     * @return string|null
     */
    public function get(string $type, ?string $provider = null, ?string $segment = null, ?string $collection = null, ?string $endpoint = null, bool $fallbackOnCurrentContext = true): ?string 
    {
        if ($fallbackOnCurrentContext) {
            $provider   ??= $this->contextService->getProvider();
            $segment    ??= $this->contextService->getSegment();
            $collection ??= $this->contextService->getCollection();
            $endpoint   ??= $this->contextService->getEndpoint();
        }

        return $this->all(
            provider  : $provider,
            segment   : $segment,
            collection: $collection,
            endpoint  : $endpoint,
        )[$type] ?? null;
    }



    /**
     * Set the template type.
     * 
     * @param string $type The template type
     * @return static
     */
    public function setType(string $type): static 
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the template type.
     * 
     * @return string The template type
     */
    public function getType(): string 
    {
        return $this->type;
    }

    /**
     * Render the template with the given options.
     * 
     * @param string $type The template type
     * @param array $options The options to replace in the template
     * @param bool $hasArray Whether to return as array or JSON string
     * @return string|array The rendered template
     */
    public function render(Response $response, string $type, bool $hasArray = false): string|array
    {
        $path       = $this->resolvePath($type);
        $template   = $this->getContent($path);

        $this->optionsBuilder->setContext('template');

        array_walk_recursive($template, function (&$v, $k) use ($response) {

            if (!is_string($v)) return;

            // Check if the value is a callable
            // --

            if ($this->isCallable($v)) {
                $v = $this->callMethod($v);
                return;
            }
            
            // Parse the expression
            // --

            $key     = null;
            $default = null;

            if (preg_match('/\{([^,}]+)(?:,\s*([^\}]+))?\}/', $v, $matches)) {
                $key = trim($matches[1]);
                $default    = isset($matches[2]) ? trim($matches[2]) : null;
            }

            // Replace with option value or default
            $v = $this->optionsBuilder->build($key, $default, [
                'response' => $response,
            ]) ?? $default ?? $v ??  null;

        });

        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        $flags |= $this->responseService->isPrettyPrint() ? JSON_PRETTY_PRINT : 0;

        return !$hasArray 
            ? json_encode($template, $flags)
            : $template 
        ;
    }

    /**
     * Loads the template file based on its format.
     * Supports JSON, PHP, XML, and YAML formats.
     * 
     * @param string $path The absolute path to the template file.
     * @throws \Exception If the file does not exist or the format is unsupported.
     */
    private function getContent(string $path) 
    {
        if (!file_exists($path)) {
            // return [];
            throw new \Exception("Template file does not exist: $path");
        }

        return match (pathinfo($path, PATHINFO_EXTENSION)) {
            // JSON
            'json'  => json_decode(file_get_contents($path), true) ?? [],

            // PHP
            'php'   => include $path,

            // XML
            'xml'   => json_decode(json_encode(simplexml_load_string(file_get_contents($path))), true) ?? [],

            // YAML
            'yml', 'yaml'  => Yaml::parseFile($path) ?? [],

            // Default
            default => throw new \Exception("Unsupported template format: $path"),
        };
    }

    /**
     * Resolves the absolute path to the template file based on provider and type.
     * The resolution follows this order:
     * 1. Checks for an absolute path defined for the provider. e.g.: /templates/my_template.yaml
     * 2. Checks for a local override in the root directory. e.g.: rootdir + provider relative path.
     * 3. Checks for standard project templates in the root directory. e.g.: rootdir/templates + provider relative path.
     * 4. Falls back to the default template in the bundle directory. e.g.: Bundle directory + default relative path.
     * 
     * @param string $provider The provider name.
     * @param string $type The template type (e.g., 'list', 'item', 'error', 'no_content').
     * @return string The absolute path to the template file.
     * @throws \Exception If the template type is unknown or the file does not exist.
     */
    private function resolvePath(string $type): string 
    {
        // Current context
        $currentRoute = $this->routeService->getCurrentRoute();
        $context      = $currentRoute ? $currentRoute['options']['context'] : [];
        // $context = $this->configuration->getContext();
        $provider = $context['provider'] ?? null;
        $segment = $context['segment'] ?? null;

        if (!$provider) {
            // return '';
            throw new \Exception("No provider defined in context");
        }

        // Resolve the path source
        $rootDir = $this->kernel->getProjectDir();
        $bundleDir = $this->kernel->getBundle('ApiBundle')->getPath();

        // Resolve the template path based on type
        // $templatePath = match($type) {
        //     Type::LIST->value      => $this->configuration->getListTemplate($provider, $segment),
        //     Type::SINGLE->value    => $this->configuration->getSingleTemplate($provider, $segment),
        //     Type::DELETE->value    => $this->configuration->getDeleteTemplate($provider, $segment),
        //     Type::ACCOUNT->value   => $this->configuration->getAccountTemplate($provider, $segment),
        //     Type::ERROR->value     => $this->configuration->getErrorTemplate($provider, $segment),
        //     Type::NOT_FOUND->value => $this->configuration->getNotFoundTemplate($provider, $segment),
        //     Type::LOGIN->value     => $this->configuration->getLoginTemplate($provider, $segment),
        //     default => throw new \Exception("Unknown template type"),
        // };

        $templatePath = $this->get($type, $provider, $segment);

        if (empty($templatePath)) {
            throw new \Exception("Unknown template type");
        }

        // Candidate paths to check
        $candidates = [
            Path::join("/", $templatePath),
            Path::join($rootDir, 'templates', $templatePath),
            Path::join($rootDir, $templatePath),
            Path::join($bundleDir, $templatePath),
        ];

        // Resolve first existing file
        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        // If no template was found, throw an exception
        throw new \Exception(sprintf(
            "Template not found for type '%s' (provider '%s'). Tried:\n%s",
            $type,
            $provider,
            implode("\n", array_map(fn($c) => " - $c", $candidates))
        ));
    }


    // ──────────────────────────────
    // Callable
    // ──────────────────────────────

    public function isCallable(string $callableString) : bool
    {
        if (strpos($callableString, '::') === false) {
            return false;
        }

        [$class, $method] = explode('::', $callableString, 2);

        if (!class_exists($class)) {
            return false;
        }

        // $instance = new $class();
        $instance = $this->container->get($class);

        if (!method_exists($instance, $method)) {
            return false;
        }

        if (!is_callable([$instance, $method])) {
            return false;
        }

        return true;
    }

    public function callMethod(string $callableString) 
    {
        if ($this->isCallable($callableString) === false) {
            return null;
        }

        [$class, $method] = explode('::', $callableString, 2);
        // $instance = new $class();
        $instance = $this->container->get($class);

        return $instance->$method();
    }
}