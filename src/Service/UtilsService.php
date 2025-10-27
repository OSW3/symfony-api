<?php 
namespace OSW3\Api\Service;


final class UtilsService
{
    /**
     * Convert string to camelCase (e.g.:: 'first_name' => 'FirstName')
     */
    public function camelize(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $string)));
    }

}