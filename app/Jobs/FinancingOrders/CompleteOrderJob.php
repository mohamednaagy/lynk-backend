<?php

namespace App\Jobs\FinancingOrders;

use App\Actions\Contracts\Orders\CompleteOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CompleteOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $orderId, public array $options = []) {}

    public function handle(): void
    {
        app(CompleteOrder::class)->handle($this->orderId, $this->options);
    }
}
