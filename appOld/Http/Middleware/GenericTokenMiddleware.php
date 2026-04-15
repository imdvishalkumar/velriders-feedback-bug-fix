<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Auth;

class GenericTokenMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('generic-auth');
        $genericToken = config('global_values.generic_token');
        if($token == ''){
            return response()->json(['status' => 'error', 'data' => null, 'message' => 'Generic Token is Required'], 401);
        }
        if ($token !== $genericToken) {
            return response()->json(['status' => 'error', 'data' => null, 'message' => 'Generic Token is Invalid'], 401);
        }

        return $next($request);
    }
}
