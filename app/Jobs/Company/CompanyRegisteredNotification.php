<?php

namespace App\Jobs\Company;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\User;
use App\Notifications\CompanyRegistered;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Grantify\Facades\Grantify;

class CompanyRegisteredNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private Company $company)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        User::permission(
            Grantify::transformSubjectActionToPermissionName([
                [
                    'subject' => Area::SuperAdmin.'-'.Subject::All,
                    'actions' => [Action::Manage],
                ],
                [
                    'subject' => Area::SuperAdmin.'-'.Subject::Companies,
                    'actions' => [Action::Edit],
                ],
            ]))
            ->chunk(10, function ($users) {
                foreach ($users as $user) {
                    $user->notify(new CompanyRegistered($this->company));
                }
            });
    }
}
