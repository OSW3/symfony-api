<?php 

namespace OSW3\Api\Service;

final class ExecutionTimeService
{
    private float $start;
    private float $end;
    private string $unit = 'ms';


    // ──────────────────────────────
    // Start / Stop
    // ──────────────────────────────

    /**
     * Start the execution time measurement.
     * 
     * @return static
     */
    public function start(): static
    {
        $this->start = microtime(true);
        return $this;
    }

    /**
     * Stop the execution time measurement.
     * 
     * @return static
     */
    public function stop(): static
    {
        $this->end = microtime(true);
        return $this;
    }


    // ──────────────────────────────
    // Unit Management
    // ──────────────────────────────

    /**
     * Set the unit for the execution time.
     * 
     * @param string $unit The unit to set ('ms' for milliseconds, 's' for seconds).
     * @return static
     */
    public function setUnit(string $unit): static
    {
        $this->unit = $unit;
        return $this;
    }

    /**
     * Get the current unit for the execution time.
     * 
     * @return string The current unit.
     */
    public function getUnit(): string
    {
        return $this->unit;
    }


    // ──────────────────────────────
    // Duration Retrieval
    // ──────────────────────────────

    /**
     * Get the duration of the execution time in the specified unit.
     * 
     * @return float The duration in the specified unit.
     */
    public function getDuration(): float
    {
        return match($this->unit) {
            'ms'    => $this->getMilliseconds(),
            's'     => $this->getSeconds(),
            default => $this->getMilliseconds(),
        };
    }

    /**
     * Get the duration in milliseconds.
     * 
     * @return float The duration in milliseconds.
     */
    private function getMilliseconds(): float
    {
        return round(($this->end - $this->start) * 1000, 6);
    }

    /**
     * Get the duration in seconds.
     * 
     * @return float The duration in seconds.
     */
    private function getSeconds(): float
    {
        return round(($this->end - $this->start), 6);
    }
}
