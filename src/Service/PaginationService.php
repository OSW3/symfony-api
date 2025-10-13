<?php
namespace OSW3\Api\Service;

use OSW3\Api\Service\RequestService;
use OSW3\Api\Service\ConfigurationService;

final class PaginationService
{
    private int $total = 0;

    public function __construct(
        private readonly RequestService $request,
        private readonly ConfigurationService $configuration,
    ){}

    private function getContext(): array 
    {
        return [
            'provider'   => $this->configuration->guessProvider(),
            'collection' => $this->configuration->guessCollection(),
            'endpoint'   => $this->configuration->guessEndpoint(),
        ];
    }

    public function getPage(): int 
    {
        $params = $this->request->getQueryParams();
        
        return $params['page'] ?? 1;
    }

    public function getTotalPages(): int 
    {
        $total = $this->getTotal();
        $limit = $this->getLimit();

        return ($total / $limit) + 1;
    }

    public function getLimit(): int 
    {
        ['provider' => $provider] = $this->getContext();

        return $this->configuration->getPaginationLimit($provider);
    }

    public function setTotal(int $total): static 
    {
        $this->total = $total;

        return $this;
    }
    public function getTotal(): int 
    {
        return $this->total;
    }

    public function getPrev(): string
    {
        $prev = $this->getPage() - 1;
        $prev = $prev <= 1 ? 1 : $prev;
        $prev = $prev >= $this->getTotalPages() ? $this->getTotalPages() : $prev;

        return $this->replacePageInUrl($prev);
    }

    public function getNext(): string
    {
        $next = $this->getPage() + 1;
        $next = $next >= $this->getTotalPages() ? $this->getTotalPages() : $next;
        $next = $next < 1 ? 1 : $next;

        return $this->replacePageInUrl($next);
    }

    public function getFirst(): string
    {
        return $this->replacePageInUrl(1);
    }

    public function getLast(): string
    {
        return $this->replacePageInUrl($this->getTotalPages());
    }

    public function isFirst(): bool
    {
        return $this->getPage() === 1;
    }

    public function isLast(): bool
    {
        return $this->getPage() === $this->getTotalPages();
    }

    public function replacePageInUrl(int $newPage): string
    {   
        $parts = parse_url($this->request->getUri());

        // Parse query string en tableau
        $query = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        // Remplace le paramètre page
        $query['page'] = $newPage;

        // Reconstruit la query
        $parts['query'] = http_build_query($query);

        // Reconstruit l'URL complète
        $scheme   = $parts['scheme'] ?? 'http';
        $host     = $parts['host'] ?? '';
        $port     = isset($parts['port']) ? ":{$parts['port']}" : '';
        $path     = $parts['path'] ?? '';
        $queryStr = $parts['query'] ? "?{$parts['query']}" : '';

        return "{$scheme}://{$host}{$port}{$path}{$queryStr}";
    }
}