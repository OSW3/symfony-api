<?php 
namespace OSW3\Api\Service;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Filesystem\Path;
use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class TemplateService 
{
    public const TEMPLATE_TYPE_LIST        = 'list';
    public const TEMPLATE_TYPE_SINGLE      = 'single';
    public const TEMPLATE_TYPE_DELETE      = 'delete';
    public const TEMPLATE_TYPE_ERROR       = 'error';
    public const TEMPLATE_TYPE_NOT_FOUND   = 'not_found';

    private string $type;

    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly ConfigurationService $configuration,
        private readonly RouteService $routeService,
        #[Autowire(service: 'service_container')] private readonly ContainerInterface $container,
    ){}


    public function setType(string $type): static 
    {
        $this->type = $type;

        return $this;
    }
    public function getType(): string 
    {
        return $this->type;
    }

    public function render(string $type, array $options = [], bool $hasArray = false): string|array
    {
        $path       = $this->resolvePath($type);
        $template   = $this->getContent($path);

        array_walk_recursive($template, function (&$v, $k) use ($options) {

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
            $v = $options[$key] ?? $default ?? $v ??  null;
        });

        return !$hasArray 
            ? json_encode($template, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : $template 
        ;
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

        if (!$provider) {
            // return '';
            throw new \Exception("No provider defined in context");
        }

        // Resolve the path source
        $rootDir = $this->kernel->getProjectDir();
        $bundleDir = $this->kernel->getBundle('ApiBundle')->getPath();

        // Resolve the template path based on type
        $templatePath = match($type) {
            static::TEMPLATE_TYPE_LIST      => $this->configuration->getListTemplate($provider),
            static::TEMPLATE_TYPE_SINGLE    => $this->configuration->getItemTemplate($provider),
            static::TEMPLATE_TYPE_DELETE    => $this->configuration->getDeleteTemplate($provider),
            static::TEMPLATE_TYPE_ERROR     => $this->configuration->getErrorTemplate($provider),
            static::TEMPLATE_TYPE_NOT_FOUND => $this->configuration->getNotFoundTemplate($provider),
            default => throw new \Exception("Unknown template type"),
        };

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