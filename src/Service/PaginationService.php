<?php
namespace OSW3\Api\Service;

use OSW3\Api\Enum\Route\DefaultEndpoint;
use OSW3\Api\Service\RequestService;
use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PaginationService
{
    private ?bool $enabledCache = null;
    private ?string $parameterPageCache = null;
    private ?string $parameterLimitCache = null;
    private int $total = 0;

    public function __construct(
        private readonly RequestService $requestService,
        private readonly ContextService $contextService,
        private readonly ConfigurationService $configurationService,
        private readonly UrlGeneratorInterface $urlGenerator,
    ){}
    
    
    /** 
     * Check if pagination is enabled for the current context
     * 
     * @return bool
     */
    public function isEnabled(): bool 
    {
        if ($this->enabledCache !== null) {
            return $this->enabledCache;
        }

        $provider   = $this->contextService->getProvider();
        $collection = $this->contextService->getCollection();
        $endpoint   = $this->contextService->getEndpoint();

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

        $this->enabledCache = $this->configurationService->isPaginationEnabled(
            provider  : $provider,
            collection: $collection,
            endpoint  : $endpoint,
        );

        return $this->enabledCache;
    }

    /**
     * Get the query parameter name for the page number
     * 
     * @return string
     */
    public function getParameterPage(): string
    {
        if ($this->parameterPageCache !== null) {
            return $this->parameterPageCache;
        }

        $this->parameterPageCache = $this->configurationService->getParameterPage(
            provider: $this->contextService->getProvider(),
        );
        
        return $this->parameterPageCache;
    }

    /**
     * Get the query parameter name for the limit
     * 
     * @return string
     */
    public function getParameterLimit(): string
    {
        if ($this->parameterLimitCache !== null) {
            return $this->parameterLimitCache;
        }

        $this->parameterLimitCache = $this->configurationService->getParameterLimit(
            provider: $this->contextService->getProvider(),
        );
        
        return $this->parameterLimitCache;
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

    /**
     * Get the number of items per page
     * 
     * @return int
     */
    public function getLimit(): ?int 
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $provider = $this->configurationService->getContext('provider');
        $default  = $this->configurationService->getPaginationLimit($provider);

        if ($this->configurationService->isPaginationLimitOverrideAllowed($provider)) {
            $params = $this->requestService->getQueryParameters();
            $key    = $this->getParameterLimit();
            $limit = (int) ($params[$key] ?? $default);
            return max(1, $limit);
        }

        return max(1, $default);
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
    public function getPrevious(): string
    {
        if (!$this->isEnabled()) {
            return '';
        }

        $prev = $this->getPage() - 1;
        $prev = $prev <= 1 ? 1 : $prev;
        $prev = $prev >= $this->getTotalPages() ? $this->getTotalPages() : $prev;

        return $this->replacePageInUrl($prev);
    }

    /**
     * Get the URL for the next page
     * 
     * @return string
     */
    public function getNext(): string
    {
        if (!$this->isEnabled()) {
            return '';
        }

        $next = $this->getPage() + 1;
        $next = $next >= $this->getTotalPages() ? $this->getTotalPages() : $next;
        $next = $next < 1 ? 1 : $next;

        return $this->replacePageInUrl($next);
    }

    /**
     * Get the URL for the current page
     * 
     * @return string
     */
    public function getSelf(): string
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
    public function getFirst(): string
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
    public function getLast(): string
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