<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Role
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $role)
    {

        if(!auth()->check()){
            return redirect()->route('auth.login');
        }

        $roles = preg_split('/[|,]/', $role);
        $roles = array_filter(array_map('trim', $roles));

        if(!in_array(auth()->user()->role, $roles, true)){
            return redirect()->route('auth.login');
        }
        
        return $next($request);
    }
}
