<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $this->isMember($request)) {
            return $this->forbiddenResponse();
        }

        $addresses = $request->user()->addresses()->latest('id')->get();

        return response()->json([
            'status' => 'success',
            'data' => AddressResource::collection($addresses),
        ]);
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        if (! $this->isMember($request)) {
            return $this->forbiddenResponse();
        }

        $data = $request->validated();
        
        if ($data['is_default'] ?? false) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        $address = $request->user()->addresses()->create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Address created successfully.',
            'data' => new AddressResource($address),
        ], 201);
    }

    public function show(Request $request, Address $address): JsonResponse
    {
        if (! $this->isMember($request)) {
            return $this->forbiddenResponse();
        }

        if ($address->user_id !== $request->user()->id) {
            return response()->json(['status' => 'error', 'message' => 'Not found.'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => new AddressResource($address),
        ]);
    }

    public function update(UpdateAddressRequest $request, Address $address): JsonResponse
    {
        if (! $this->isMember($request)) {
            return $this->forbiddenResponse();
        }

        if ($address->user_id !== $request->user()->id) {
            return response()->json(['status' => 'error', 'message' => 'Not found.'], 404);
        }

        $data = $request->validated();
        
        if ($data['is_default'] ?? false) {
            $request->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Address updated successfully.',
            'data' => new AddressResource($address),
        ]);
    }

    public function destroy(Request $request, Address $address): JsonResponse
    {
        if (! $this->isMember($request)) {
            return $this->forbiddenResponse();
        }

        if ($address->user_id !== $request->user()->id) {
            return response()->json(['status' => 'error', 'message' => 'Not found.'], 404);
        }

        $address->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Address deleted successfully.',
        ]);
    }

    private function isMember(Request $request): bool
    {
        return $request->user()?->role === 'member';
    }

    private function forbiddenResponse(): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Only members can manage addresses.',
        ], 403);
    }
}
