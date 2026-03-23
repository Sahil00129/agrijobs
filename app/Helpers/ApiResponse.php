<?php

if (!function_exists('errorResponse')) {
    function errorResponse($message = 'Something went wrong', $statusCode = 200, $data = [])
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'statuscode' => $statusCode,
            'data' => $data
        ], $statusCode);
    }
}

if (!function_exists('successResponse')) {
    function successResponse($message = 'Success', $data = [], $statusCode = 200)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'statuscode' => $statusCode,
            'data' => $data
        ], $statusCode);
    }
}
