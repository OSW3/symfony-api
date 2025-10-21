<?php 

namespace OSW3\Api\Service;

final class ExecutionTimeService
{
    private float $start;
    private float $end;
    private string $unit = 'ms';

    public function start(): static
    {
        $this->start = microtime(true);
        return $this;
    }
    public function stop(): static
    {
        $this->end = microtime(true);
        return $this;
    }

    public function setUnit(string $unit): static
    {
        $this->unit = $unit;
        return $this;
    }
    public function getUnit(): string
    {
        return $this->unit;
    }

    public function getDuration(): float
    {
        return match($this->unit) {
            'ms'    => $this->getMilliseconds(),
            's'     => $this->getSeconds(),
            default => $this->getMilliseconds(),
        };
    }

    private function getMilliseconds(): float
    {
        return round(($this->end - $this->start) * 1000, 6);
    }

    private function getSeconds(): float
    {
        return round(($this->end - $this->start), 6);
    }
}
