<?php 
namespace OSW3\Api\Services;

class ErrorService 
{
    private string $message;

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getMessage(): ?string 
    {
        return $this->message ?? null;
    }

    public function hasError(): bool 
    {
        return !!$this->getMessage();
    }
}