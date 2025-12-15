<?php
namespace OSW3\Api\Service;

use OSW3\Api\Service\RequestService;
use OSW3\Api\Service\EndpointService;
use OSW3\Api\Service\ProviderService;
use OSW3\Api\Service\CollectionService;
use OSW3\Api\Enum\Route\DefaultEndpoint;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PaginationService
{
    private int $total = 0;

    public function __construct(
        private readonly ContextService $contextService,
        private readonly RequestService $requestService,
        private readonly ProviderService $providerService,
        private readonly EndpointService $endpointService,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly CollectionService $collectionService,
    ){}

    /**
     * Get the pagination configuration for the given context
     * 
     * @param string|null $provider
     * @param string|null $collection
     * @param string|null $endpoint
     * @return array
     */
    private function options(?string $provider, ?string $collection = null, ?string $endpoint = null): array
    {
        if (! $this->providerService->exists($provider)) {
            return [];
        }

        // 1. Endpoint-specific route
        if ($collection && $endpoint) {
            $endpointOptions = $this->endpointService->get($provider, ContextService::SEGMENT_COLLECTION, $collection, $endpoint);
            if ($endpointOptions && isset($endpointOptions['pagination'])) {
                return $endpointOptions['pagination'];
            }
        }

        // 2. Collection-level route
        if ($collection) {
            $collectionOptions = $this->collectionService->get($provider, ContextService::SEGMENT_COLLECTION, $collection);
            if ($collectionOptions && isset($collectionOptions['pagination'])) {
                return $collectionOptions['pagination'];
            }
        }

        // 3. Global default route
        $providerOptions = $this->providerService->get($provider);
        return $providerOptions['pagination'] ?? [];
    }


    // -- CONFIG OPTIONS GETTERS
    
    /**
     * Check if pagination is enabled for the given context
     * 
     * @param string|null $provider
     * @param string|null $collection
     * @param string|null $endpoint
     * @return bool
     */
    public function isEnabled(?string $provider = null, ?string $collection = null, ?string $endpoint = null, bool $fallbackOnCurrentContext = true): bool 
    {
        if ($fallbackOnCurrentContext) {
            $provider   ??= $this->contextService->getProvider();
            $collection ??= $this->contextService->getCollection();
            $endpoint   ??= $this->contextService->getEndpoint();
        }

        if (in_array(strtolower($endpoint), [
            DefaultEndpoint::EDIT->value,
            DefaultEndpoint::DELETE->value,
            DefaultEndpoint::PATCH->value,
            DefaultEndpoint::PUT->value,
            DefaultEndpoint::READ->value,
            DefaultEndpoint::SHOW->value,
            DefaultEndpoint::UPDATE->value
        ], true  )) {
            return false;
        }

        return $this->options(
            provider  : $provider,
            collection: $collection,
            endpoint  : $endpoint,
        )['enabled'] ?? true;
    }

    /**
     * Get the "page" parameter name for the given context
     * 
     * @param string|null $provider
     * @param string|null $collection
     * @return string
     */
    public function getParameterPage(?string $provider = null, ?string $collection = null, bool $fallbackOnCurrentContext = true): string
    {
        if ($fallbackOnCurrentContext) {
            $provider   ??= $this->contextService->getProvider();
            $collection ??= $this->contextService->getCollection();
        }
        
        return $this->options(
            provider  : $provider,
            collection: $collection,
        )['parameters']['page'] ?? 'page';
    }

    /**
     * Get the "limit" parameter name for the given context
     * 
     * @param string|null $provider
     * @param string|null $collection
     * @return string
     */
    public function getParameterLimit(?string $provider = null, ?string $collection = null, bool $fallbackOnCurrentContext = true): string
    {
        if ($fallbackOnCurrentContext) {
            $provider   ??= $this->contextService->getProvider();
            $collection ??= $this->contextService->getCollection();
        }
        
        return $this->options(
            provider  : $provider,
            collection: $collection,
        )['parameters']['limit'] ?? 'limit';
    }


    // ──────────────────────────────
    // Pages
    // ──────────────────────────────

    /**
     * Get the total number of pages
     * 
     * @return int
     */
    public function getTotalPages(): int 
    {
        if (!$this->isEnabled()) {
            return 1;
        }

        $items = $this->getTotalItems();
        $limit = $this->getLimit();
        $pages = (int) ceil($items / $limit);

        return $pages > 0 ? $pages : 1;
    }

    /**
     * Get the current page number
     * 
     * @return int
     */
    public function getPage(): int 
    {
        if (!$this->isEnabled()) {
            return 1;
        }

        $params = $this->requestService->getQueryParameters();
        $key    = $this->getParameterPage();
        $page   = (int) ($params[$key] ?? 1);

        return max(1, $page);
    }

    /**
     * Get the current page number (alias of getPage)
     * 
     * @return int
     */
    public function getCurrentPage(): int 
    {
        if (!$this->isEnabled()) {
            return 1;
        }

        return $this->getPage();
    }

    public function getPreviousPage(): int 
    {
        if (!$this->isEnabled()) {
            return 1;
        }

        $prev = $this->getPage() - 1;
        $prev = $prev <= 1 ? 1 : $prev;
        $prev = $prev >= $this->getTotalPages() ? $this->getTotalPages() : $prev;

        return $prev;
    }

    public function getNextPage(): int 
    {
        if (!$this->isEnabled()) {
            return 1;
        }

        $next = $this->getPage() + 1;
        $next = $next >= $this->getTotalPages() ? $this->getTotalPages() : $next;
        $next = $next < 1 ? 1 : $next;

        return $next;
    }


    // ──────────────────────────────
    // Items
    // ──────────────────────────────

    /**
     * Set the total number of items
     * 
     * @param int $total
     * @return static
     */
    public function setTotal(int $total): static 
    {
        $this->total = $total;

        return $this;
    }

    /**
     * Get the total number of items
     * 
     * @return int
     */
    public function getTotal(): int 
    {
        if (!$this->isEnabled()) {
            return 0;
        }

        return $this->total;
    }

    /**
     * Get the total number of items (alias of getTotal)
     * 
     * @return int
     */
    public function getTotalItems(): int 
    {
        if (!$this->isEnabled()) {
            return 0;
        }

        return $this->getTotal();
    }


    // ──────────────────────────────
    // Limit & Offset
    // ──────────────────────────────

    public function getDefaultLimit(?string $provider = null, ?string $collection = null, ?string $endpoint = null, bool $fallbackOnCurrentContext = true): ?int 
    {
        if (!$this->isEnabled()) {
            return -1;
        }

        if ($fallbackOnCurrentContext) {
            $provider   ??= $this->contextService->getProvider();
            $collection ??= $this->contextService->getCollection();
            $endpoint   ??= $this->contextService->getEndpoint();
        }
        
        return $this->options(
            provider  : $provider,
            collection: $collection,
            endpoint  : $endpoint,
        )['limit'] ?? 10;
    }

    public function getMaxLimit(?string $provider = null, ?string $collection = null, ?string $endpoint = null, bool $fallbackOnCurrentContext = true): ?int 
    {
        if (!$this->isEnabled()) {
            return -1;
        }

        if ($fallbackOnCurrentContext) {
            $provider   ??= $this->contextService->getProvider();
            $collection ??= $this->contextService->getCollection();
            $endpoint   ??= $this->contextService->getEndpoint();
        }
        
        return $this->options(
            provider  : $provider,
            collection: $collection,
            endpoint  : $endpoint,
        )['max_limit'] ?? 100;
    }

    /**
     * Get the number of items per page
     * 
     * @return int
     */
    public function getLimit(): ?int 
    {
        if (!$this->isEnabled()) {
            return -1;
        }

        $provider   = $this->contextService->getProvider();
        $collection = $this->contextService->getCollection();
        $endpoint   = $this->contextService->getEndpoint();
        
        $limit  = $this->getDefaultLimit(
            provider  : $provider,
            collection: $collection,
            endpoint  : $endpoint,
        );

        $max  = $this->getMaxLimit(
            provider  : $provider,
            collection: $collection,
            endpoint  : $endpoint,
        );

        $override  = $this->options(
            provider  : $provider,
            collection: $collection,
            endpoint  : $endpoint,
        )['allow_limit_override'] ?? false;

        if ($override) {
            $params = $this->requestService->getQueryParameters();
            $key    = $this->getParameterLimit();
            $limit = (int) ($params[$key] ?? $limit);
            // return max(1, $limit);
        }

        if ($max !== null && $limit > $max) {
            $limit = $max;
        }
        
        return max(1, $limit);
    }
    
    /**
     * Get the offset for the current page
     * 
     * @return int
     */
    public function getOffset(): ?int
    {
        if (!$this->isEnabled()) {
            return null;
        }

        return ($this->getPage() - 1) * $this->getLimit();
    }


    // ──────────────────────────────
    // Urls
    // ──────────────────────────────

    /**
     * Get the URL for the previous page
     * 
     * @return string
     */
    public function getPreviousUrl(): string
    {
        return $this->isEnabled() 
            ? $this->replacePageInUrl($this->getPreviousPage()) 
            : '';
    }

    /**
     * Get the URL for the next page
     * 
     * @return string
     */
    public function getNextUrl(): string
    {
        return $this->isEnabled() 
            ? $this->replacePageInUrl($this->getNextPage()) 
            : '';
    }

    /**
     * Get the URL for the current page
     * 
     * @return string
     */
    public function getCurrentUrl(): string
    {
        if (!$this->isEnabled()) {
            return '';
        }

        return $this->replacePageInUrl($this->getPage());
    }

    /**
     * Get the URL for the first page
     * 
     * @return string
     */
    public function getFirstUrl(): string
    {
        if (!$this->isEnabled()) {
            return '';
        }

        return $this->replacePageInUrl(1);
    }

    /**
     * Get the URL for the last page
     * 
     * @return string
     */
    public function getLastUrl(): string
    {
        if (!$this->isEnabled()) {
            return '';
        }

        return $this->replacePageInUrl($this->getTotalPages());
    }


    // ──────────────────────────────
    // Flags
    // ──────────────────────────────

    /**
     * Check if the current page is the first page
     * 
     * @return bool
     */
    public function isFirstPage(): bool
    {
        if (!$this->isEnabled()) {
            return true;
        }

        return $this->getPage() === 1;
    }

    /**
     * Check if the current page is the last page
     * 
     * @return bool
     */
    public function isLastPage(): bool
    {
        if (!$this->isEnabled()) {
            return true;
        }

        return $this->getPage() === $this->getTotalPages();
    }

    /**
     * Check if there is a previous page
     * 
     * @return bool
     */
    public function hasPreviousPage(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        return !$this->isFirstPage();
    }

    /**
     * Check if there is a next page
     * 
     * @return bool
     */
    public function hasNextPage(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        return !$this->isLastPage();
    }

    /**
     * Replace the "page" parameter in the current URL with a new value
     * 
     * @param int $newPage
     * @return string
     */
    public function replacePageInUrl(int $newPage): string
    {
        $params = $this->requestService->getQueryParameters();
        $key    = $this->getParameterPage();

        if (isset($params[$key])) {
            $params[$key] = $newPage;
        } else {
            $params = array_merge($params, [$key => $newPage]);
        }

        return $this->urlGenerator->generate(
            $this->requestService->getCurrentRoute(), 
            $params, 
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }
}