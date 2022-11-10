<?php

use App\Actions\Contracts\Wallets\WalletService;
// use App\Support\Wallets\WalletService;
use App\Models\User;
// use App\Models\Webhook;
use App\Models\Wallet;
use App\Support\Webhooks\Facades\Webhook;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('test', function () {
    dd(Webhook::fire());
    // return User::first()->getMorphClass();
    // return (new WalletService)->create(User::first(), ['name' => 'ahmed']);
    // return dd(app(WalletService::class)->findByUuidOrFail('9862d9d7-c205-4403-a34b-a46cb4192163a'));
    // return Wallet::get();
    // return (new WebhookEventManager(Company::first(), WebhookType::OrderUpdates, ['key' => 'value']))->test();
});
