<?php
namespace OSW3\Api\Service;

use OSW3\Api\Service\RequestService;
use OSW3\Api\Service\ConfigurationService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PaginationService
{
    private int $total = 0;

    public function __construct(
        private readonly RequestService $request,
        private readonly ConfigurationService $configuration,
        private readonly UrlGeneratorInterface $urlGenerator,
    ){}

    /**
     * Get the total number of pages
     * 
     * @return int
     */
    public function getTotalPages(): int 
    {
        $total = $this->getTotal();
        $limit = $this->getLimit();

        return (int) ceil($total / $limit);
    }

    /**
     * Get the current page number
     * 
     * @return int
     */
    public function getPage(): int 
    {
        $params = $this->request->getQueryParams();
        $page = (int) ($params['page'] ?? 1);
        return max(1, $page);
    }

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
        return $this->total;
    }

    /**
     * Get the number of items per page
     * 
     * @return int
     */
    public function getLimit(): int 
    {
        $provider = $this->configuration->guessProvider();
        $default  = $this->configuration->getPaginationLimit($provider);

        if ($this->configuration->isPaginationLimitOverrideAllowed($provider)) {
            $params = $this->request->getQueryParams();
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
        return ($this->getPage() - 1) * $this->getLimit();
    }

    /**
     * Get the URL for the previous page
     * 
     * @return string
     */
    public function getPrev(): string
    {
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
        return $this->replacePageInUrl($this->getPage());
    }

    /**
     * Get the URL for the first page
     * 
     * @return string
     */
    public function getFirst(): string
    {
        return $this->replacePageInUrl(1);
    }

    /**
     * Get the URL for the last page
     * 
     * @return string
     */
    public function getLast(): string
    {
        return $this->replacePageInUrl($this->getTotalPages());
    }

    /**
     * Check if the current page is the first page
     * 
     * @return bool
     */
    public function isFirst(): bool
    {
        return $this->getPage() === 1;
    }

    /**
     * Check if the current page is the last page
     * 
     * @return bool
     */
    public function isLast(): bool
    {
        return $this->getPage() === $this->getTotalPages();
    }

    /**
     * Check if there is a previous page
     * 
     * @return bool
     */
    public function hasPrev(): bool
    {
        return !$this->isFirst();
    }

    /**
     * Check if there is a next page
     * 
     * @return bool
     */
    public function hasNext(): bool
    {
        return !$this->isLast();
    }

    /**
     * Replace the "page" parameter in the current URL with a new value
     * 
     * @param int $newPage
     * @return string
     */
    public function replacePageInUrl(int $newPage): string
    {
        $params = $this->request->getQueryParams();

        if (isset($params['page'])) {
            $params['page'] = $newPage;
        } else {
            $params = array_merge($params, ['page' => $newPage]);
        }

        return $this->urlGenerator->generate(
            $this->request->getCurrentRoute(), 
            $params, 
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }
}