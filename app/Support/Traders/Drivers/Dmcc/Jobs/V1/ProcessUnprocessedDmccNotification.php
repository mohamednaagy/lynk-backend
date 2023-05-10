<?php

namespace App\Support\Traders\Drivers\Dmcc\Jobs\V1;

use App\Exceptions\TraderNotSupportedException;
use App\Models\TraderOrder;
use App\Support\Traders\Events\ProcessNotification;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\Traits\StopsTraderOrderOnJobFailure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessUnprocessedDmccNotification implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, StopsTraderOrderOnJobFailure;

    protected string $ttiId;

    protected string $notificationId;

    protected mixed $notification;

    protected $traderOrder;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($notification)
    {
        $this->notification = $notification;
        $this->notificationId = $this->notification->notificationHeaderAndEntity->notificationId;
        $this->ttiId = $this->notification->notificationHeaderAndEntity->notificationEntityDetails->notificationEntity[0]->entityValue;
    }

    /**
     * Execute the job.
     *
     * @return void
     *
     * @throws TraderNotSupportedException
     */
    public function handle(): void
    {
        $driver = config('trader.default');

        if (! in_array($driver, ['dmcc', 'fake'])) {
            throw new TraderNotSupportedException;
        }

        DB::transaction(function () use ($driver) {
            $this->traderOrder = TraderOrder::query()
                ->where('reference', $this->ttiId)
                ->lockForUpdate()
                ->first();

            if (! $this->traderOrder) {
                return;
            }

            $trader = Trader::driver($driver);
            $trader->processNotification($this->notificationId);

            ProcessNotification::dispatch('trader', [], [], now());
        });
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping($this->uniqueId())];
    }

    public function uniqueId(): string
    {
        return __CLASS__.'_'.$this->notificationId;
    }
}
