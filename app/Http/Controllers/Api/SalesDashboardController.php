<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SalesDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesDashboardController extends Controller
{
    public function index(Request $request, SalesDashboardService $dashboardService): JsonResponse
    {
        $request->validate([
            'store_id' => ['sometimes', 'integer', 'exists:stores,id'],
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $dashboardService->summary($request->query('store_id')),
        ]);
    }
}
