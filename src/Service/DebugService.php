<?php 
namespace OSW3\Api\Service;

use Symfony\Component\HttpKernel\Profiler\Profiler;

final class DebugService 
{    
    public function __construct(
        private readonly ?Profiler $profiler = null,
        private readonly ContextService $contextService,
        private readonly ConfigurationService $configurationService,
    ){}

    public function isEnabled(): bool
    {
        $provider = $this->contextService->getProvider();
        return $this->configurationService->isDebugEnabled($provider);
    }


    // ──────────────────────────────
    // Memory
    // ──────────────────────────────

    public function getMemoryUsage(): int 
    {
        if (!$this->isEnabled()) {
            return 0;
        }

        if (!function_exists('memory_get_usage')) {
            return 0;
        }

        return memory_get_usage(true);
    }
    public function getMemoryPeak(): int 
    {
        if (!$this->isEnabled()) {
            return 0;
        }

        if (!function_exists('memory_get_peak_usage')) {
            return 0;
        }

        return memory_get_peak_usage(true);
    }


    // ──────────────────────────────
    // Cache
    // ──────────────────────────────


    public function getLogLevel(): string
    {
        if (!$this->isEnabled()) {
            return 'none';
        }

        return $_ENV['APP_LOG_LEVEL'] ?? 'info';
    }

    public function getIncludedFiles(): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        if (!function_exists('get_included_files')) {
            return [];
        }

        return get_included_files();
    }
    public function getCountIncludedFiles(): int
    {
        if (!$this->isEnabled()) {
            return 0;
        }

        return count($this->getIncludedFiles());
    }
}