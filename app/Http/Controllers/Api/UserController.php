<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * @return array<int, string>
     */
    private function manageableRoles(User $actor): array
    {
        if ($actor->role === 'main_admin') {
            return ['main_admin', 'branch_admin', 'cashier', 'member'];
        }
        if ($actor->role === 'branch_admin') {
            return ['branch_admin', 'cashier', 'member'];
        }
        if ($actor->role === 'cashier') {
            return ['member'];
        }
        return [];
    }

    public function index(Request $request): JsonResponse
    {
        $actor = $request->user();

        if (! $actor || ! in_array($actor->role, ['main_admin', 'branch_admin', 'cashier'], true)) {
            return $this->forbiddenResponse();
        }

        $query = User::query()
            ->with('store')
            ->latest('id');

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('role')) {
            $query->where('role', $request->query('role'));
        }

        if ($request->has('store_id')) {
            $query->where('store_id', $request->query('store_id'));
        }

        if (in_array($actor->role, ['branch_admin', 'cashier'], true)) {
            if ($actor->store_id === null) {
                return $this->forbiddenResponse('You must be assigned to a store.');
            }

            $query
                ->where('store_id', $actor->store_id)
                ->whereIn('role', $this->manageableRoles($actor));
        }

        return response()->json([
            'status' => 'success',
            'data' => UserResource::collection($query->get()),
        ]);
    }

    public function store(UserStoreRequest $request): JsonResponse
    {
        $actor = $request->user();

        if (! $actor || ! in_array($actor->role, ['main_admin', 'branch_admin', 'cashier'], true)) {
            return $this->forbiddenResponse();
        }

        $data = $request->validated();

        if (in_array($actor->role, ['branch_admin', 'cashier'], true)) {
            $staffGuard = $this->guardStoreStaffPayload($actor, $data);

            if ($staffGuard) {
                return $staffGuard;
            }
        }

        if ($actor->role === 'main_admin') {
            $mainAdminGuard = $this->guardMainAdminPayload($data);

            if ($mainAdminGuard) {
                return $mainAdminGuard;
            }
        }

        $user = User::query()->create($this->mapUserAttributes($data));

        return response()->json([
            'status' => 'success',
            'message' => 'User created successfully.',
            'data' => new UserResource($user->load('store')),
        ], 201);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();

        if (! $this->canManageTargetUser($actor, $user)) {
            return $this->forbiddenResponse();
        }

        return response()->json([
            'status' => 'success',
            'data' => new UserResource($user->load('store')),
        ]);
    }

    public function update(UserUpdateRequest $request, User $user): JsonResponse
    {
        $actor = $request->user();

        if (! $this->canManageTargetUser($actor, $user)) {
            return $this->forbiddenResponse();
        }

        $data = $request->validated();

        if (in_array($actor->role, ['branch_admin', 'cashier'], true)) {
            $staffGuard = $this->guardStoreStaffPayload($actor, $data);

            if ($staffGuard) {
                return $staffGuard;
            }
        }

        if ($actor->role === 'main_admin') {
            $effectiveData = $data;
            $effectiveData['role'] = $effectiveData['role'] ?? $user->role;
            $effectiveData['store_id'] = array_key_exists('store_id', $effectiveData)
                ? $effectiveData['store_id']
                : $user->store_id;

            $mainAdminGuard = $this->guardMainAdminPayload($effectiveData);

            if ($mainAdminGuard) {
                return $mainAdminGuard;
            }
        }

        $user->forceFill($this->mapUserAttributes($data))->save();

        return response()->json([
            'status' => 'success',
            'message' => 'User updated successfully.',
            'data' => new UserResource($user->load('store')),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();

        if (! $this->canManageTargetUser($actor, $user)) {
            return $this->forbiddenResponse();
        }

        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'User deleted successfully.',
        ]);
    }

    private function canManageTargetUser(?User $actor, User $target): bool
    {
        if (! $actor) {
            return false;
        }

        if ($actor->role === 'main_admin') {
            return true;
        }

        if (! in_array($actor->role, ['branch_admin', 'cashier'], true)) {
            return false;
        }

        if (! in_array($target->role, $this->manageableRoles($actor), true)) {
            return false;
        }

        return $actor->store_id !== null && (int) $actor->store_id === (int) $target->store_id;
    }

    private function guardStoreStaffPayload(User $actor, array &$payload): ?JsonResponse
    {
        if ($actor->store_id === null) {
            return $this->forbiddenResponse('You must be assigned to a store.');
        }

        if (array_key_exists('role', $payload) && ! in_array((string) $payload['role'], $this->manageableRoles($actor), true)) {
            return $this->forbiddenResponse("{$actor->role} can only manage: " . implode(', ', $this->manageableRoles($actor)) . '.');
        }

        if (array_key_exists('store_id', $payload) && (int) $payload['store_id'] !== (int) $actor->store_id) {
            return $this->forbiddenResponse("{$actor->role} can only manage users in their own store.");
        }

        $payload['store_id'] = $actor->store_id;

        return null;
    }

    private function guardMainAdminPayload(array $payload): ?JsonResponse
    {
        $role = isset($payload['role']) ? (string) $payload['role'] : null;
        $storeId = array_key_exists('store_id', $payload) ? $payload['store_id'] : null;

        if ($role === 'branch_admin' && $storeId === null) {
            return $this->unprocessableResponse('branch_admin must be assigned to a branch store.');
        }

        if ($storeId === null) {
            return null;
        }

        $store = Store::query()->find($storeId);

        if (! $store) {
            return $this->unprocessableResponse('Selected store is invalid.');
        }

        if ($role === 'main_admin' && $store->type !== 'main') {
            return $this->unprocessableResponse('main_admin must be assigned to a main store.');
        }

        if ($role === 'branch_admin' && $store->type !== 'branch') {
            return $this->unprocessableResponse('branch_admin must be assigned to a branch store.');
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function mapUserAttributes(array $payload): array
    {
        if (array_key_exists('full_name', $payload) && is_string($payload['full_name'])) {
            $payload['name'] = $payload['full_name'];
        }

        unset($payload['full_name']);

        return $payload;
    }

    private function forbiddenResponse(string $message = 'You are not authorized to manage users.'): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
        ], 403);
    }

    private function unprocessableResponse(string $message): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
        ], 422);
    }
}
