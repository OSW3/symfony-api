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
        $this->code = $code;

        return $this;
    }
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
}