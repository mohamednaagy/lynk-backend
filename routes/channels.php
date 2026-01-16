<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return $user instanceof User && (int) $user->id === (int) $id;
});
// NOTICE: this will resolve to `private-user-notifications.{id}` but we should never add `private-` here
Broadcast::channel('user-notifications.{id}', function ($user, $id) {
    return $user instanceof User && (int) $user->id === (int) $id;
});
