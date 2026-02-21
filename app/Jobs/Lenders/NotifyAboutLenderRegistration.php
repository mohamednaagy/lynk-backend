<?php

namespace App\Jobs\Lenders;

use App\Models\Lender;
use App\Notifications\LenderRegistered;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyAboutLenderRegistration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private Lender $lender)
    {
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $notification = new LenderRegistered($this->lender);
        // no need to pass emails, since the admins emails will be included internally as BCC
        $notification->sendTo([]);
    }
}
