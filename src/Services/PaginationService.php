<?php 
namespace OSW3\SymfonyApi\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaginationService
{
    private Request $request;
    private int $items;
    private int $offset;
    private int $perPage;
    private int $page;
    private int $pages;
    private int $previous;
    private int $next;
    private string $route;
    private array $params;
    private bool $isAbsolute = false;
    private bool $isReady = false;

    public function __construct(
        private RequestStack $requestStack,
        private UrlGeneratorInterface $urlGenerator,
    ){
        $this->request = $requestStack->getCurrentRequest();
        $this->page = $this->request->get('page') ?? 1;
    }

    public function execute(): static 
    {
        $offset = ($this->page * $this->perPage) - $this->perPage;
        $this->offset = $offset;

        $pages = $this->items / $this->perPage;
        $pages = ceil($pages);
        $pages = intval($pages);
        $pages = $pages < 1 ? 1 : $pages;
        $this->pages = $pages;

        $previous = $this->page - 1 < 1 ? 1 : $this->page - 1;
        $this->previous = $previous;

        $next = $this->page + 1 > $this->pages ? $this->pages : $this->page + 1;
        $this->next = $next;

        $this->isReady = true;

        return $this;
    }

    public function isReady(): bool
    {
        return $this->isReady;
    }

    public function response(): array 
    {
        return [
            'page' => $this->page,
            'pages' => $this->pages,
            'items' => $this->items,
            'per_page' => $this->perPage,
            'urls' => [
                'first'    => $this->link(1),
                'previous' => $this->link($this->previous),
                'next'     => $this->link($this->next),
                'last'     => $this->link($this->pages),
            ]
        ];
    }

    public function setItems(int $items): static 
    {
        $this->items = $items;

        return $this;
    }

    public function setPerPage(int $perPage): static 
    {
        $this->perPage = $perPage;
        
        return $this;
    }

    public function setRoute(string $route): static 
    {
        $this->route = $route;

        return $this;
    }

    public function setParams(array $params): static
    {
        $this->params = $params;

        return $this;
    }

    public function setIsAbsolute(bool $isAbsolute): static 
    {
        $this->isAbsolute = $isAbsolute;

        return $this;
    }

    public function getPage(): int 
    {
        return $this->page;
    }

    public function getPages(): int 
    {
        return $this->pages;
    }

    public function getOffset(): int 
    {
        return $this->offset;
    }

    private function link(int $page): string
    {
        $route = $this->route;
        $params = array_merge($this->params, ['page' => $page]);
        $absolute = $this->isAbsolute;
        return $this->urlGenerator->generate($route, $params, $absolute);
    }
}