<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetCurrentUser
{
    public function handle(Request $request, Closure $next)
    {
        if (!session()->has('current_user_id')) {
            session(['current_user_id' => 1]);
        }
        return $next($request);
    }
}
