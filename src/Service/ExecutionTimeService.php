<?php 

namespace OSW3\Api\Service;

use OSW3\Api\Enum\Logger\ExecutionTimeUnit;

final class ExecutionTimeService
{
    private float $start;
    private float $end;
    private string $unit = ExecutionTimeUnit::MILLISECOND->value;


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
            ExecutionTimeUnit::SECOND->value      => $this->getSeconds(),
            ExecutionTimeUnit::MILLISECOND->value => $this->getMilliseconds(),
            ExecutionTimeUnit::MICROSECOND->value => $this->getMicroseconds(),
            ExecutionTimeUnit::NANOSECOND->value  => $this->getNanoseconds(),
            ExecutionTimeUnit::FEMTOSECOND->value => $this->getFemtoseconds(),
            default                               => $this->getMilliseconds(),
        };
    }

    /**
     * Get the duration in seconds.
     * 
     * @return float The duration in seconds.
     */
    private function getSeconds(): float
    {
        if (!isset($this->end)) {
            $this->stop();
        }

        return round(($this->end - $this->start), 6);
    }

    /**
     * Get the duration in milliseconds.
     * 
     * @return float The duration in milliseconds.
     */
    private function getMilliseconds(): float
    {
        return round(($this->getSeconds() * 1000), 6);
    }

    /**
     * Get the duration in microseconds.
     * 
     * @return float The duration in microseconds.
     */
    private function getMicroseconds(): float
    {
        return round(($this->getMilliseconds() * 1000), 6);
    }

    /**
     * Get the duration in nanoseconds.
     * 
     * @return float The duration in nanoseconds.
     */
    private function getNanoseconds(): float
    {
        return round(($this->getMicroseconds() * 1000), 6);
    }

    /**
     * Get the duration in femtoseconds.
     * 
     * @return float The duration in femtoseconds.
     */
    private function getFemtoseconds(): float
    {
        return round(($this->getNanoseconds() * 1000), 6);
    }
}
