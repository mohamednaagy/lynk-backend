<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        User::onlyTrashed()
            ->orderBy('id')
            ->chunk(100, function ($users) {
                foreach ($users as $user) {
                    $user->update(['email' => User::DELETED_MODEL_EMAIL_PREFIX.$user->email]);
                }
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        User::onlyTrashed()
            ->orderBy('id')
            ->chunk(100, function ($users) {
                foreach ($users as $user) {
                    $user->update(['email' => explode(User::DELETED_MODEL_EMAIL_PREFIX, $user->email)[1]]);
                }
            });
    }
};
