<?php 
namespace OSW3\Api\Controller;

use Symfony\Component\HttpFoundation\Response;

class PlaceholderController
{
    public function handle(): Response
    {
        return new Response('', 204);
    }
}