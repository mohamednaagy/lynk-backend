<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;  

class HorizonAuthMiddleware  
{  
    const INVALID_CREDENTIALS_MESSAGE = 'Invalid credentials. Please try again.';  
    
    public function handle(Request $request, Closure $next)  
    {  
        $username = config('horizon_username');  
        $password = config('horizon_password');  
        if (is_null($request->getUser()) || is_null($request->getPassword())) {  
            return response()->view('horizon.login');  
        }  
        dd($request->all());
        if ($request->getUser() !== $username || $request->getPassword() !== $password) {  
            Log::warning('Unauthorized access attempt with username: ' . $request->getUser());  
            return response(self::INVALID_CREDENTIALS_MESSAGE, 401);  
        }  

        return $next($request);  
    }  
}   
