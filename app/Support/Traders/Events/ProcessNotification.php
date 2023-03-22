<?php

namespace App\Support\Traders\Events;

use App\Contracts\LogServiceRequest;
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProcessNotification implements LogServiceRequest
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        protected string $driver,
        protected array $requestData,
        protected array $responseData,
        protected Carbon $timestamp
    ) {
    }

    public function name(): string
    {
        return $this->driver;
    }

    public function subject(): null|Model
    {
        return null;
    }

    public function causer(): null|Model
    {
        return null;
    }

    public function extraProperties(): array
    {
        return [
            'driver' => $this->driver,
            'request' => $this->requestData,
            'response' => $this->responseData,
        ];
    }

    public function description(): string
    {
        return "{$this->driver}.{$this->name()}";
    }

    public function timestamp(): Carbon
    {
        return $this->timestamp;
    }
}
