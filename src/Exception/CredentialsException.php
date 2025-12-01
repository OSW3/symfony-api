<?php 
namespace OSW3\Api\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CredentialsException extends BadRequestHttpException
{
    /**
     * @param string $message
     * @param array|null $errors Structured field errors (optional)
     */
    public function __construct()
    {
        $message = 'Invalid credentials';
        parent::__construct($message);
    }
}
