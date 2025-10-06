<?php 
namespace OSW3\Api\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class AccessDeniedException extends HttpException
{
    public function __construct(string $message = 'Access Denied', \Throwable $previous = null, int $code = 0)
    {
        // TODO: Custom response "Access denied"
        parent::__construct(403, $message, $previous, [], $code);
    }
}
