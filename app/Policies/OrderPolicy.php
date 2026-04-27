<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['main_admin', 'branch_admin', 'cashier'], true);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Order $order): bool
    {
        if ($user->role === 'main_admin') {
            return true;
        }

        if (in_array($user->role, ['branch_admin', 'cashier'], true)) {
            return $user->store_id === $order->store_id;
        }

        if ($user->role === 'member') {
            return $user->id === $order->customer_id;
        }

        return false;
    }

    /**
     * Determine whether the user can manage the order (shipping, status, etc.).
     */
    public function manage(User $user, Order $order): bool
    {
        if ($user->role === 'main_admin') {
            return true;
        }

        if ($user->role === 'branch_admin') {
            return $user->store_id === $order->store_id;
        }

        return false;
    }
}
