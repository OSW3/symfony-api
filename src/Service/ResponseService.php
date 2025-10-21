<?php 
namespace OSW3\Api\Service;

use Symfony\Component\Yaml\Yaml;
use OSW3\Api\HttpFoundation\XmlResponse;
use OSW3\Api\Service\ConfigurationService;
use OSW3\Api\Service\ResponseStatusService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ResponseService 
{
    public function __construct(
        private readonly ResponseStatusService $status,
        private readonly ConfigurationService $configuration,
    ){}


    // ──────────────────────────────
    // Builder
    // ──────────────────────────────

    public function buildJsonResponse(array $data, int $statusCode = 200): Response 
    {
        return new JsonResponse($data, $statusCode);
    }

    public function buildXmlResponse(array $data, int $statusCode = 200): Response
    {
        return XmlResponse::fromArray($data, $statusCode);
    }

    public function buildYamlResponse(array $data, int $statusCode = 200): Response 
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/x-yaml');
        $response->setContent(Yaml::dump($data, 4, 2, Yaml::DUMP_OBJECT_AS_MAP));
        $response->setStatusCode($statusCode);
        return $response;
    }


    public function build(array $payload): Response
    {
        $context    = $this->configuration->getContext();
        $provider   = $context['provider'] ?? null;

        $statusCode = $this->status->getCode();
        // $payload    = $this->payload($data);


        // Build the response by format (with $payload and $statusCode)
        // --

        $response = match ($this->configuration->getResponseFormat($provider)) {
            'json'  => $this->buildJsonResponse($payload, $statusCode),
            'xml'   => $this->buildXmlResponse($payload, $statusCode),
            'yaml'  => $this->buildYamlResponse($payload, $statusCode),
            default => $this->buildJsonResponse($payload, $statusCode),
        };


        // Add and modify headers
        // --

        // $this->headers->init($response->headers);

        // if ($this->configuration->getVersionHeaderFormat($provider) === 'header') 
        // {
        //     $this->headers->addApiVersion();
        // }


        // $this->headers->addCacheControl();


        // dump($response->headers->all());

        // foreach ($this->headers->all() as $key => $value) 
        // {
        //     $response->headers->set($key, $value);
        // }
        // dd($response->headers->all());


        // $response->headers->set('X-App-Version', '1.2.3');
        // $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
        // $response->headers->set('Access-Control-Allow-Origin', '*');

        // $response->setContent(json_encode($payload));
        // $response->setStatusCode($statusCode);

        return $response;
    }


    // ──────────────────────────────
    // Response times
    // ──────────────────────────────

    // public function getTimestamp(): string 
    // {
    //     return gmdate('c');
    // }

}