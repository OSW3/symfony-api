<?php 
namespace OSW3\Api\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class PlaceholderController extends AbstractController
{
    public function handle(): Response
    {
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}