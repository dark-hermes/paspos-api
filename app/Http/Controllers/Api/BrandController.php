<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBrandRequest;
use App\Http\Requests\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BrandController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return $this->forbiddenResponse();
        }

        $query = Brand::query()
            ->withCount('products')
            ->latest('id');

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where('name', 'like', "%{$search}%");
        }

        $brands = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => BrandResource::collection($brands),
        ]);
    }

    public function store(StoreBrandRequest $request): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return $this->forbiddenResponse();
        }

        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('brands', 'public');
        }

        unset($data['logo']);

        $brand = Brand::query()->create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Brand created successfully.',
            'data' => new BrandResource($brand->loadCount('products')),
        ], 201);
    }

    public function show(Request $request, Brand $brand): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return $this->forbiddenResponse();
        }

        return response()->json([
            'status' => 'success',
            'data' => new BrandResource($brand->loadCount('products')),
        ]);
    }

    public function update(UpdateBrandRequest $request, Brand $brand): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return $this->forbiddenResponse();
        }

        $data = $request->validated();

        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($brand->logo_path) {
                Storage::disk('public')->delete($brand->logo_path);
            }

            $data['logo_path'] = $request->file('logo')->store('brands', 'public');
        }

        unset($data['logo']);

        $brand->fill($data)->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Brand updated successfully.',
            'data' => new BrandResource($brand->loadCount('products')),
        ]);
    }

    public function destroy(Request $request, Brand $brand): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return $this->forbiddenResponse();
        }

        // Delete logo file if exists
        if ($brand->logo_path) {
            Storage::disk('public')->delete($brand->logo_path);
        }

        $brand->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Brand deleted successfully.',
        ]);
    }

    private function isAdmin(Request $request): bool
    {
        return in_array($request->user()?->role, ['main_admin', 'branch_admin']);
    }

    private function forbiddenResponse(): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Only admins can manage brands.',
        ], 403);
    }
}
