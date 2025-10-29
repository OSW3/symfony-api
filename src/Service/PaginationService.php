<?php
namespace OSW3\Api\Service;

use OSW3\Api\Service\RequestService;
use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PaginationService
{
    private int $total = 0;

    public function __construct(
        private readonly RequestService $requestService,
        private readonly ContextService $contextService,
        private readonly ConfigurationService $configurationService,
        private readonly UrlGeneratorInterface $urlGenerator,
    ){}
    

    public function isEnabled(): bool 
    {
        $provider   = $this->contextService->getProvider();
        $collection = $this->contextService->getCollection();
        $endpoint   = $this->contextService->getEndpoint();

        return $this->configurationService->isPaginationEnabled($provider, $collection, $endpoint);
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

        $params = $this->requestService->getQueryParams();
        $page   = (int) ($params['page'] ?? 1);

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
    public function getLimit(): int 
    {
        if (!$this->isEnabled()) {
            return 0;
        }

        $provider = $this->configurationService->getContext('provider');
        $default  = $this->configurationService->getPaginationLimit($provider);

        if ($this->configurationService->isPaginationLimitOverrideAllowed($provider)) {
            $params = $this->requestService->getQueryParams();
            $limit = (int) ($params['limit'] ?? $default);
            return max(1, $limit);
        }

        return max(1, $default);
    }
    
    /**
     * Get the offset for the current page
     * 
     * @return int
     */
    public function getOffset(): int
    {
        if (!$this->isEnabled()) {
            return 0;
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
        $params = $this->requestService->getQueryParams();

        if (isset($params['page'])) {
            $params['page'] = $newPage;
        } else {
            $params = array_merge($params, ['page' => $newPage]);
        }

        return $this->urlGenerator->generate(
            $this->requestService->getCurrentRoute(), 
            $params, 
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }
}