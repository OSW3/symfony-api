<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Enum\MimeType;
use OSW3\Api\Service\ContextService;
use OSW3\Api\Service\RequestService;
use OSW3\Api\Service\ProviderService;

final class ResponseService 
{
    private int $size = 0;
    private int $count = 0;

    public function __construct(
        private readonly ContextService $contextService,
        private readonly RequestService $requestService,
        private readonly ProviderService $providerService,
    ){}

    /**
     * Get the response options for a specific provider
     * 
     * @param string|null $provider
     * @return array
     */
    private function options(?string $provider): array 
    {
        if (! $this->providerService->exists($provider)) {
            return [];
        }

        $providerOptions = $this->providerService->get($provider);
        return $providerOptions['response'] ?? [];
    }


    // -- CONFIG OPTIONS GETTERS



    // -- COMPUTED GETTERS

    /**
     * Get the response format
     * Gets the default format from configuration
     * Allows override via query parameter if enabled
     * 
     * @return string
     */
    public function getFormat(): string
    {
        $provider = $this->contextService->getProvider();

        // Get the format from configuration
        $format = $this->options($provider)['format']['type'] ?? 'json';

        // Check if format override is allowed
        if ($this->options($provider)['content_negotiation']['enabled'] ?? false) 
        {
            // Get the current request
            $request = $this->requestService->getCurrentRequest();

            // Get the parameter name for format override
            $param = $this->options($provider)['content_negotiation']['parameter'] ?? 'format';

            // Retrieve the custom format from query parameters
            $custom = $request->query->get($param);
            
            // Validate and set the custom format if it's valid
            if (in_array($custom, array_keys(MimeType::toArray(true)), true)) {
                $format = $custom;
            }
        }

        return $format;
    }

    /**
     * Get the response MIME type based on format or configuration
     * 
     * @return string
     */
    public function getMimeType(): string
    {
        $provider = $this->contextService->getProvider();

        // Get the MIME type from configuration
        $mimetype = $this->options($provider)['format']['mime_type'] ?? null;

        if ($mimetype !== null) {
            return $mimetype;
        }

        $format = $this->getFormat();
        return MimeType::fromFormat($format)->value;
    }

    /**
     * Set response size
     * The length in bytes of the response content (data)
     * 
     * @param int $size
     * @return static
     */
    public function setSize(int $size): static 
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get response size
     * 
     * @return int
     */
    public function getSize(): int 
    {
        return $this->size;
    }

    /**
     * Set response count
     * The number of items in the response content (data)
     * 
     * @param int $count
     * @return static
     */
    public function setCount(int $count): static 
    {
        $this->count = $count;

        return $this;
    }

    /**
     * Get response count
     * 
     * @return int
     */
    public function getCount(): int 
    {
        return $this->count;
    }

    public function isPrettyPrint(): bool 
    {
        $provider = $this->contextService->getProvider();

        // Get the pretty print option from configuration
        return $this->options($provider)['pretty_print']['enabled'] ?? false;
    }
}