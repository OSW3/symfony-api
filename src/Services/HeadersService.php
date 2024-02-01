<?php 
namespace OSW3\Api\Services;

use Symfony\Component\HttpFoundation\Response;

class HeadersService
{
    const EXCLUDES = [
        'X-Powered-By'
    ];

    private int $statusCode;
    private Response $response;
    private array $headers = [];

    public function __construct(
        private ErrorService $errorService,
    )
    {
        $this->setStatusCode( Response::HTTP_OK );
    }

    public function setStatusCode(int $statusCode): static
    {
        $this->statusCode = $statusCode;

        if ($statusCode > 299 && !$this->errorService->hasError())
        {
            $this->errorService->setMessage($this->getStatusText());
        }

        return $this;
    }

    public function getStatusCode(): int 
    {
        return $this->statusCode;
    }

    public function getStatusText(): string 
    {
        return Response::$statusTexts[$this->statusCode];
    }

    public function setResponse(Response $response): static
    {
        $this->response = $response;

        return $this;
    }

    public function builder(): static
    {
        return $this
            ->appProperties()
            ->excludeProperties()
        ;
    }

    public function appProperties(): static
    {
        $this
            ->headersContentLength()
            ->headersAccessControl()
        ;

        array_walk($this->headers, fn($value, $property) => $this->response->headers->set($property, $value));
        
        return $this;
    }

    private function excludeProperties(): static 
    {
        $excludes = [];

        // Add excluded properties here

        $excludes = array_merge($excludes, self::EXCLUDES);

        array_walk($excludes, fn($property) => header_remove($property));

        return $this;
    }

    private function headersContentLength(): static
    {
        $content = $this->response->getContent();
        
        $this->headers['Content-Length'] = mb_strlen($content, '8bit');

        return $this;
    }

    // C.O.R.S.
    private function headersAccessControl(): static
    {
        $this->headers['Access-Control-Allow-Origin']      = '*';
        $this->headers['Access-Control-Allow-Credentials'] = 'true';
        $this->headers['Access-Control-Allow-Methods']     = '';
        $this->headers['Access-Control-Allow-Headers']     = 'DNT, X-User-Token, Keep-Alive, User-Agent, X-Requested-With, If-Modified-Since, Cache-Control, Content-Type';
        $this->headers['Access-Control-Max-Age']           = 1728000;

        return $this;
    }
}