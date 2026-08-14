<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Spatie\Permission\Exceptions\UnauthorizedException;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }


    // protected function unauthenticated($request, AuthenticationException $exception)
    // {
    //     if ($request->expectsJson()) {
    //         return response()->json(['error' => 'Unauthenticated.'], 401);
    //     }

    //     return redirect()->guest(route('send-otp'));
    // }

    // protected function prepareJsonResponse($request, Throwable $e)
    // {
    //     if ($e instanceof UnauthorizedHttpException) {
    //         return response()->json(['error' => 'Unauthorized.'], 401);
    //     }

    //     return parent::prepareJsonResponse($request, $e);
    // }

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof MethodNotAllowedHttpException) {
            return response()->json([
                'status' => 'error',
                'data' => null,
                'message' => 'Method not allowed.',
            ], 405);
        } else if ($exception instanceof RouteNotFoundException) {
            return response()->json([
                'status' => 'error',
                'data' => null,
                'message' => 'Route not found.',
            ], 404);
        } else if ($exception instanceof UnauthorizedException) {
            return redirect()->route('admin.booking.calculation');
        }

        return parent::render($request, $exception);
    }

}
