<?php

namespace App\Support\Sms\Events;

use App\Contracts\LogServiceRequest;
use App\Enums\SmsEvent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SmsSent implements LogServiceRequest
{
    use Dispatchable, SerializesModels;

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
        return SmsEvent::Sent;
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
