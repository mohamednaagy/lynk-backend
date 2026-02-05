<?php

namespace App\Services\Health;

class CheckResult
{
    public function __construct(
        public readonly string $name,
        public readonly string $status,
        public readonly float $durationMs,
        public readonly array $meta = [],
    ) {}

    public function isHealthy(): bool
    {
        return $this->status === 'healthy';
    }

    public function toArray(): array
    {
        return array_filter([
            'status' => $this->status,
            'duration_ms' => $this->durationMs,
            ...$this->meta,
        ], fn ($value) => $value !== null);
    }
}
