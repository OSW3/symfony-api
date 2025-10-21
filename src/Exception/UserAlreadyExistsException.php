<?php 
namespace OSW3\Api\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UserAlreadyExistsException extends HttpException
{
    public function __construct()
    {
        $statusCode = Response::HTTP_CONFLICT;
        $message    = 'User already exists';

        parent::__construct($statusCode, $message);
    }
}
