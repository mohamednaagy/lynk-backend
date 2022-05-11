<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

class LoginController extends Controller
{
    /**
     * Handle an authentication attempt.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function authenticate(Request $request)
    {
        $requestData = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'source' => ['required', 'string']
        ]);

        $user = User::where('email', $requestData['email'])->first();

        if ($user === null || !Hash::check($requestData['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed')
            ]);
        }

        $responseData = [];

        if (EnsureFrontendRequestsAreStateful::fromFrontend($request)) {
            Auth::login($user);
            $request->session()->regenerate();
        } else {
            $responseData['token'] = $user->createToken($requestData['source'])->plainTextToken;
        }

        return response()->json($responseData);
    }

    /**
     * Handle logout attempt.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        if (EnsureFrontendRequestsAreStateful::fromFrontend($request)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        } else {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([], 204);
    }
}
