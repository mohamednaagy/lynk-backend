<?php

namespace App\Services\Health\Checks;

use App\Services\Health\CheckResult;
use App\Services\Health\Contracts\HealthCheck;
use RuntimeException;
use Throwable;

class NightwatchCheck implements HealthCheck
{
    public function name(): string
    {
        return 'nightwatch';
    }

    public function run(): CheckResult
    {
        $start = microtime(true);

        if (! env('NIGHTWATCH_ENABLED')) {
            return new CheckResult(
                name: $this->name(),
                status: 'skipped',
                durationMs: round((microtime(true) - $start) * 1000, 2),
                meta: ['reason' => 'not_enabled'],
            );
        }

        try {
            $ingestUri = env('NIGHTWATCH_INGEST_URI');
            $url = 'http://'.$ingestUri;
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_NOBODY => true,
            ]);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new RuntimeException('Nightwatch agent unreachable: '.$error);
            }

            return new CheckResult(
                name: $this->name(),
                status: 'healthy',
                durationMs: round((microtime(true) - $start) * 1000, 2),
                meta: ['http_code' => $httpCode],
            );
        } catch (Throwable $e) {
            return new CheckResult(
                name: $this->name(),
                status: 'unhealthy',
                durationMs: round((microtime(true) - $start) * 1000, 2),
                meta: ['error' => $e->getMessage()],
            );
        }
    }
}
