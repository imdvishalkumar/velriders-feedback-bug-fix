<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function validationErrorResponse($validator)
    {
        $firstError = $validator->errors()->first();
        return response()->json([
            'status' => 'error',
            'data' => [
                'errors' => $validator->errors(),
            ],
            'message' => $firstError,
        ], 200);
    }

    public function errorResponse($message)
    {
        return response()->json([
            'status' => 'error',
            'data' => null,
            'message' => $message,
        ]);
    }

    public function successResponse($data, $message = '')
    {
        return response()->json([
            'status' => 'success',
            'data' => $data,
            'message' => $message,
        ]);
    }

}
