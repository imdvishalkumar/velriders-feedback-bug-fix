<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if($request->routeIs('admin.get.login') && auth()->guard('admin_web')->check()){
            return redirect()->route('admin.users');
        }

        if($request->is('admin') || $request->is('admin/*')){
            if(!auth()->guard('admin_web')->check()){
                //return redirect()->route('admin.login');
                return redirect()->route('admin.get.login');
            }
        }

        return $next($request);
    }
}
