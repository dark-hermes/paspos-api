<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStoreRequest;
use App\Http\Requests\StoreUpdateRequest;
use App\Http\Resources\StoreResource;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $this->isMainAdmin($request)) {
            return $this->forbiddenResponse();
        }

        $query = Store::query()
            ->withCount('users')
            ->latest('id');

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($request->has('type')) {
            $query->where('type', $request->query('type'));
        }

        $stores = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => StoreResource::collection($stores),
        ]);
    }

    public function store(StoreStoreRequest $request): JsonResponse
    {
        if (! $this->isMainAdmin($request)) {
            return $this->forbiddenResponse();
        }

        $store = Store::query()->create($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Store created successfully.',
            'data' => new StoreResource($store->loadCount('users')),
        ], 201);
    }

    public function show(Request $request, Store $store): JsonResponse
    {
        if (! $this->isMainAdmin($request)) {
            return $this->forbiddenResponse();
        }

        return response()->json([
            'status' => 'success',
            'data' => new StoreResource($store->loadCount('users')),
        ]);
    }

    public function update(StoreUpdateRequest $request, Store $store): JsonResponse
    {
        if (! $this->isMainAdmin($request)) {
            return $this->forbiddenResponse();
        }

        $store->fill($request->validated())->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Store updated successfully.',
            'data' => new StoreResource($store->loadCount('users')),
        ]);
    }

    public function destroy(Request $request, Store $store): JsonResponse
    {
        if (! $this->isMainAdmin($request)) {
            return $this->forbiddenResponse();
        }

        $store->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Store deleted successfully.',
        ]);
    }

    private function isMainAdmin(Request $request): bool
    {
        return $request->user()?->role === 'main_admin';
    }

    private function forbiddenResponse(): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Only main_admin can manage stores.',
        ], 403);
    }
}
