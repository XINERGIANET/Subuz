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
        $roles = array_filter(array_map(static function ($r) {
            return strtolower(trim((string) $r));
        }, $roles));

        $userRole = strtolower(trim((string) auth()->user()->role));

        if(!in_array($userRole, $roles, true)){
            return redirect()->route('auth.login');
        }
        
        return $next($request);
    }
}
