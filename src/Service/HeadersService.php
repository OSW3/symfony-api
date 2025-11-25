<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Helper\HeaderHelper;
use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\ConfigurationService;

final class HeadersService 
{

    public function __construct(
        private readonly ContextService $contextService,
        private readonly ConfigurationService $configurationService,
    ){}
    

    // Headers configurations

    /**
     * Determine if the 'X-' prefix should be stripped from headers.
     * 
     * @return bool True if 'X-' prefix should be stripped, false otherwise
     */
    public function stripXPrefix(): bool 
    {
        return $this->configurationService->isHeadersStripXPrefix(
            provider: $this->contextService->getProvider()
        ) ?? false;
    }

    /**
     * Determine if legacy headers should be kept.
     * 
     * @return bool True if legacy headers should be kept, false otherwise
     */
    public function keepLegacy(): bool 
    {
        return $this->configurationService->isHeadersKeepLegacy(
            provider: $this->contextService->getProvider()
        ) ?? false;
    }

    /**
     * Get the exposed header directives.
     * 
     * @return array The exposed header directives
     */
    public function getExposedDirectives(): array 
    {
        $directives = $this->configurationService->getHeadersExposedDirectives(
            provider: $this->contextService->getProvider()
        );

        foreach ($directives as $key => $value) {
            unset($directives[$key]);
            $key = HeaderHelper::toHeaderCase($key);
            $directives[$key] = $value;
        }

        return $directives;
    }

    // /**
    //  * Get the vary header directives.
    //  * 
    //  * @return array The vary header directives
    //  */
    // public function getVaryDirectives(): array 
    // {
    //     $directives = $this->configurationService->getHeadersVaryDirectives(
    //         provider: $this->contextService->getProvider()
    //     );

    //     return array_values(array_filter($directives));
    // }

    // /**
    //  * Get the removed header directives.
    //  * 
    //  * @return array The removed header directives
    //  */
    // public function getRemovedDirectives(): array 
    // {
    //     $directives = $this->configurationService->getHeadersRemoveDirectives(
    //         provider: $this->contextService->getProvider()
    //     );

    //     return array_values(array_filter($directives));
    // }
}