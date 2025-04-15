<?php

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

//use Illuminate\Support\Facades\Route;
//
//Route::get('test', function () {
//    return 'hello';
//});

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

use Illuminate\Support\Facades\DB;

\Illuminate\Support\Facades\Route::get('ttt', function () {
    //
    //    $t = DB::table('trader_orders')->where('id', 153608)->first();
    //    $t = \App\Models\TraderOrder::whereId(153608)->first()->refresh()->toArray();
    //    dd($t);
});

\Illuminate\Support\Facades\Route::get('test', function () {
    $arr = [];
    $arr = [];
    for ($i = 0; $i < 2000000; $i++) {
        $arr[] = ['name' => 'test'.rand(111, 922299999)];

        // Insert in batches
        if (count($arr) == 10000) {
            \Illuminate\Support\Facades\DB::table('random_data')->insert($arr);
            $arr = []; // Reset the array for the next batch
        }
    }

    // Insert any remaining records
    if (count($arr) > 0) {
        \Illuminate\Support\Facades\DB::table('random_data')->insert($arr);
    }
});
