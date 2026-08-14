<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use JWTAuth;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HtttpKernel\Exception\UnauthorizedHttpException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

class CarHostAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $guard = 'api-carhost'): Response
    {
        $header_token = $request->header('Authorization');
        $token = trim(str_ireplace('bearer', '', $header_token));
        if (!$token) {
            return response()->json(['status' => 'error', 'data' => null, 'message' => 'Token is Required'], 401);
        }
        if ($guard) {
            auth()->shouldUse($guard);
        }

        try{
            //JWTAuth::setToken($token);
            //$carHostUser = JWTAuth::parseToken()->authenticate();
            $carHostUser = auth()->guard('api-carhost')->user();
            if (!$carHostUser) {
                return response()->json(['status' => 'error', 'data' => null, 'message' => 'User not found'], 404);
            }
           // print_r($carHostUser); die;
        }catch(JWTException $e){
            if($e instanceof \PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException){
                return response()->json(['status' => 'error','data' => null,'message' => 'Token is Expired']);
            }else if($e instanceof \PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException){
                return response()->json(['status' => 'error','data' => null,'message' => 'Token is Invalid']);
            }else{
                return response()->json(['status' => 'error','data' => null,'message' => 'Token is Required']);
            }
        }

        return $next($request);
    }

}
