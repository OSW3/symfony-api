<?php 
namespace OSW3\Api\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class InvalidRegistrationDataException extends BadRequestHttpException
{
    /**
     * @param string $message
     * @param array|null $errors Structured field errors (optional)
     */
    public function __construct()
    {
        // $message = 'Invalid registration data';
        $message = 'Username and password are required';
        parent::__construct($message);
    }
}
