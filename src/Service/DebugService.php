<?php 
namespace OSW3\Api\Service;

use OSW3\Api\Service\RequestService;
use Symfony\Component\HttpKernel\Profiler\Profiler;

final class DebugService 
{    
    public function __construct(
        private readonly ?Profiler $profiler = null,
        private readonly RequestService $request,
    ){}


    // ──────────────────────────────
    // Memory
    // ──────────────────────────────

    public function getMemoryUsage(): int 
    {
        return memory_get_usage(true);
    }
    public function getMemoryPeak(): int 
    {
        return memory_get_peak_usage(true);
    }


    // ──────────────────────────────
    // Cache
    // ──────────────────────────────


    public function getLogLevel(): string
    {
        return $_ENV['APP_LOG_LEVEL'] ?? 'info';
    }

    public function getIncludedFiles(): array
    {
        return get_included_files();
    }
    public function getCountIncludedFiles(): int
    {
        return count($this->getIncludedFiles());
    }


    /**
     * measure the latency between the reception of the request by the server and its actual processing
     * 
     * @return float
     */
    // public function getQueueTime(): ?float
    // {
    //     if (!$this->request) {
    //         return null;
    //     }

    //     $headers = $this->request->headers;

    //     // Recherche du header (héritage des load balancers classiques)
    //     $queueStart =
    //         $headers->get('X-Request-Start') ??
    //         $headers->get('X-Queue-Start') ??
    //         $headers->get('Request-Start');

    //     if (!$queueStart) {
    //         return null; // Pas de header => impossible à calculer
    //     }

    //     // Nettoyage éventuel : certains formats contiennent "t=" ou "@" ou sont en microsecondes
    //     $queueStart = preg_replace('/[^0-9\.]/', '', $queueStart);

    //     // Conversion en secondes si timestamp trop grand
    //     if ($queueStart > 9999999999) { // microsecondes
    //         $queueStart /= 1_000_000;
    //     } elseif ($queueStart > 9999999999 / 1000) { // millisecondes
    //         $queueStart /= 1000;
    //     }

    //     // Calcul du temps écoulé entre réception et traitement
    //     $now = microtime(true);
    //     $queueTime = $now - (float) $queueStart;

    //     return round($queueTime, 3); // secondes, arrondi à 3 décimales
    // }




    // public function getApiCallCount(): int
    // {
    //     return $this->apiCalls;
    // }

    // public function getExternalCallCount(): int
    // {
    //     return $this->externalCalls;
    // }

    // public function getQueueTime(): ?float
    // {
    //     return $this->queueTime;
    // }

    // public function getRequestId(): string
    // {
    //     // ID interne pour corrélation avec logs
    //     return $this->requestId ??= bin2hex(random_bytes(8));
    // }

        


}