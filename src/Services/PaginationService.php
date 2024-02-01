<?php 
namespace OSW3\Api\Services;

use OSW3\Api\Services\RequestService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaginationService 
{
    private bool $status = false;
    private array $params = [];

    private int $first = 1;
    private int $last = 1;
    private int $pages = 1;
    private int $items = 0;

    public function __construct(
        private ConfigurationService $configuration,
        private ErrorService $errorService,
        private HeadersService $headersService,
        private RequestService $requestService,
        private UrlGeneratorInterface $urlGenerator,
    ){}

    // Pagination status
    // --

    public function isEnabled(): bool 
    {
        $provider = $this->requestService->getProvider();
        return $this->configuration->isPaginationEnabled($provider);
    }

    public function setStatus(bool $status): static
    {
        $this->status = $this->isEnabled() ? $status : false;

        return $this;
    }
    public function isActive(): bool 
    {
        return $this->status;
    }


    // Page number
    // --

    public function getPage(): int
    {
        $page = $this->requestService->getPage();

        if ($page > $this->getPages())
        {
            $this->errorService->setMessage(sprintf("The page %s does not exist.", $page));
            $this->headersService->setStatusCode(Response::HTTP_NOT_FOUND);
        }

        return $page;
    }

    public function getFirst(): int 
    {
        return $this->first;
    }

    public function getPrevious(): int 
    {
        $page     = $this->getPage();
        $first    = $this->getFirst();
        $previous = $page - 1;

        if ($previous < $first)
        {
            $previous = $first;
        }

        return $previous;
    }

    public function getLast(): int 
    {
        return $this->last;
    }

    public function getNext(): int 
    {
        $page = $this->getPage();
        $last = $this->getLast();
        $next = $page + 1;
        
        if ($next > $last)
        {
            $next = $last;
        }

        return $next;
    }





    public function getPerPage(): ?int
    {
        return $this->requestService->getItemsPerPage();
    }

    public function getOffset(): int|null
    {
        $perPage = $this->getPerPage();
        $page    = $this->getPage();
        $offset  = ($page * $perPage) - $perPage;;

        return $offset;
    }

    public function setPages(int $items): static
    {
        $pages = $items / $this->getPerPage();
        $pages = ceil($pages);
        $pages = intval($pages);
        $pages = $pages < 1 ? 1 : $pages;
        
        $this->items = $items;
        $this->pages = $pages;
        $this->last  = $pages;

        return $this;
    }
    public function getPages(): int
    {
        return $this->pages;
    }
    public function getItems(): int
    {
        return $this->items;
    }


    // Pagination Link
    // --

    public function setParams(array $params): static
    {
        $this->params = $params;

        return $this;
    }
    public function link(int $page): string
    {
        $route = $this->requestService->getRoute();
        $provider = $this->requestService->getProvider();
        $isAbsolute  = $this->configuration->isAbsoluteLink($provider);
        $params = array_merge($this->params, ['page' => $page]);

        return $this->urlGenerator->generate($route, $params, !$isAbsolute);
    }

    public function urls(): array 
    {
        return [
            'first'    => $this->link( $this->getFirst() ),
            'previous' => $this->link( $this->getPrevious() ),
            'next'     => $this->link( $this->getNext() ),
            'last'     => $this->link( $this->getLast() ),
        ];
    }
}