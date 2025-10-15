<?php 
namespace OSW3\Api\Service;

use Symfony\Component\HttpFoundation\Response;

final class ResponseStatusService 
{
    private int $code = Response::HTTP_OK;

    /**
     * Code (200, 404, 301, ...)
     */
    public function setCode(int $code): static 
    {
        if (!array_key_exists($code, Response::$statusTexts)) {
            throw new \InvalidArgumentException("Code HTTP invalide : {$code}");
        }

        $this->code = $code;

        return $this;
    }

    /**
     * Code (200, 404, 301, ...)
     * 
     * @return int HTTP status code
     */
    public function getCode(): int 
    {
        return $this->code;
    }

    /**
     * Text (OK)
     */
    public function getText(): string 
    {
        return Response::$statusTexts[ $this->getCode() ];
    }

    /**
     * State (success, failed, error)
     */
    public function getState(): string 
    {
        $code = $this->getCode();

        return match (true) {
            $code >= 200 && $code < 300 => 'success',
            $code >= 400 && $code < 500 => 'failed',
            default                     => 'error',
        };
    }

    /**
     * Check if the response is a success (2xx)
     * 
     * @return bool True if the response is a success, false otherwise
     */
    public function isSuccess(): bool
    {
        return $this->getState() === 'success';
    }

    /**
     * Check if the response is a failure (4xx)
     * 
     * @return bool True if the response is a failure, false otherwise
     */
    public function isFailed(): bool
    {
        return $this->getState() === 'failed';
    }

    /**
     * Check if the response is an error (5xx)
     * 
     * @return bool True if the response is an error, false otherwise
     */
    public function isError(): bool
    {
        return $this->getState() === 'error';
    }

}