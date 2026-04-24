<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InsufficientStockException extends Exception
{
    /**
     * Render the exception as an HTTP response.
     */
    public function render($request): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Insufficient stock for this operation. ' . $this->getMessage(),
        ], 422);
    }
}
