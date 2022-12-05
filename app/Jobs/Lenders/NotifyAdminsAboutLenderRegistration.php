<?php

namespace App\Jobs\Lenders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\User;
use App\Notifications\LenderRegistered;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Modules\Grantify\Facades\Grantify;

class NotifyAdminsAboutLenderRegistration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private Company $company, private string $externalUrl)
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
        $users = User::role([Role::Admin, Role::Manager])
            ->permission(
                Grantify::transformSubjectActionToPermissionName([
                    [
                        'subject' => Area::SuperAdmin.'-'.Subject::All,
                        'actions' => [Action::Manage],
                    ],
                    [
                        'subject' => Area::SuperAdmin.'-'.Subject::Lenders,
                        'actions' => [Action::Edit],
                    ],
                ]))
            ->get();

        Notification::send($users, new LenderRegistered($this->company, $this->externalUrl));
    }
}
