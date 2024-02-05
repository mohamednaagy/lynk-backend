<?php

namespace App\Actions\Contracts;

use App\Models\User;
use Illuminate\Http\Request;

interface VerifyOtp
{
    public function handle(string $vid, string $code, ?Request $request = null): User;
}
