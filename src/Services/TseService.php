<?php 
namespace OSW3\SymfonyApi\Services;

class TseService
{
    private int $start = 0;

    public function __construct()
    {
        $this->start = $this->time();
    }

    public function duration(): int
    {
        return $this->time() - $this->start;
    }

    private function time(): int
    {
        return intval(microtime(true) * 10000);
    }
}