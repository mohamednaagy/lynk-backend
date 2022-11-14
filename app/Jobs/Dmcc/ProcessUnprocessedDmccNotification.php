<?php

namespace App\Jobs\Dmcc;

use App\Support\Traders\Facades\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class ProcessUnprocessedDmccNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $notificationId;

    protected mixed $notification;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($notification)
    {
        $this->notification = $notification;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $driver = config('trader.default');
        $trader = Trader::driver($driver);
        $this->notificationId = $this->notification->notificationHeaderAndEntity->notificationId;
        $trader->processNotification($this->notificationId);
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping('notificationId'.$this->notificationId)];
    }
}
