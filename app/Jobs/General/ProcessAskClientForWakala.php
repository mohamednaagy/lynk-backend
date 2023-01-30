<?php

namespace App\Jobs\General;

use App\Actions\Contracts\Clients\AskClientWakala;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class ProcessAskClientForWakala implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected mixed $financingOrder;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($financingOrder)
    {
        $this->financingOrder = $financingOrder;
    }

    /**
     * Execute the job.
     *
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function handle(): void
    {
        /** @var FinancingOrder $financingOrder */
        $financingOrder = FinancingOrder::query()->lockForUpdate()->findOrFail($this->financingOrder);

        if ($financingOrder->status->cantMoveTo(FinancingOrderStatus::WaitingClientWakala)) {
            return;
        }

        if (! $financingOrder->is_verification_required) {
            return;
        }

        app()->make(AskClientWakala::class)->handle(
            $financingOrder,
            Str::replace('{order_id}', $financingOrder->id, Config::get('frontend.client_wakala_url'))
        );

        $financingOrder->update([
            'status' => FinancingOrderStatus::WaitingClientWakala,
        ]);
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping('financingOrder'.$this->financingOrder)];
    }
}
