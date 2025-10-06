<?php
namespace OSW3\Api\Exception;

use RuntimeException;

final class RepositoryCallException extends RuntimeException
{
    public static function invalid(string $repository, ?string $method = null): self
    {
        $msg = "Repository '{$repository}'";
        if ($method) {
            $msg .= " or method '{$method}'";
        }
        $msg .= " is invalid or not callable.";
        
        return new self($msg);
    }
}
