<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Inventory;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Reusable service for creating stock movements and auto-updating inventory.
 */
class StockMovementService
{
    /**
     * Create a stock movement and auto-adjust inventory stock.
     *
     * @param array{
     *     src_store_id?: int|null,
     *     dest_store_id?: int|null,
     *     product_id: int,
     *     quantity: float|int,
     *     type: string,
     *     title: string,
     *     note?: string|null,
     * } $data
     * @return StockMovement
     */
    public function create(array $data): StockMovement
    {
        $this->validateBusinessCases($data['type'], $data['src_store_id'] ?? null, $data['dest_store_id'] ?? null);

        return DB::transaction(function () use ($data) {
            $movement = StockMovement::query()->create($data);

            $this->adjustInventory($movement);

            return $movement;
        });
    }

    /**
     * Reverse a stock movement's effect on inventory (for deletion).
     *
     * @param StockMovement $movement
     * @return void
     */
    public function reverse(StockMovement $movement): void
    {
        DB::transaction(function () use ($movement) {
            $this->reverseInventory($movement);
            $movement->delete();
        });
    }

    /**
     * Validates business logic for IN, OUT, and TRANSFER.
     */
    private function validateBusinessCases(string $type, ?int $srcStoreId, ?int $destStoreId): void
    {
        if ($type === 'in') {
            if ($srcStoreId !== null) {
                throw new InvalidArgumentException("For 'in' movement, source store must be null.");
            }
            if ($destStoreId === null) {
                throw new InvalidArgumentException("For 'in' movement, destination store must be provided.");
            }
        } elseif ($type === 'out') {
            if ($srcStoreId === null) {
                throw new InvalidArgumentException("For 'out' movement, source store must be provided.");
            }
            if ($destStoreId !== null) {
                throw new InvalidArgumentException("For 'out' movement, destination store must be null.");
            }
        } elseif ($type === 'transfer') {
            if ($srcStoreId === null || $destStoreId === null) {
                throw new InvalidArgumentException("For 'transfer' movement, both source and destination stores must be provided.");
            }
            if ($srcStoreId === $destStoreId) {
                throw new InvalidArgumentException("For 'transfer' movement, source and destination stores cannot be the same.");
            }
        }
    }

    /**
     * Adjust inventory stock based on movement type.
     *
     * @param StockMovement $movement
     * @return void
     */
    private function adjustInventory(StockMovement $movement): void
    {
        $quantity = (float) $movement->quantity;

        if ($movement->type === 'in') {
            $this->incrementStock($movement->dest_store_id, $movement->product_id, $quantity);
        } elseif ($movement->type === 'out') {
            $this->decrementStock($movement->src_store_id, $movement->product_id, $quantity);
        } elseif ($movement->type === 'transfer') {
            $this->decrementStock($movement->src_store_id, $movement->product_id, $quantity);
            $this->incrementStock($movement->dest_store_id, $movement->product_id, $quantity);
        }
    }

    /**
     * Reverse the inventory adjustment (undo the movement effect).
     *
     * @param StockMovement $movement
     * @return void
     */
    private function reverseInventory(StockMovement $movement): void
    {
        $quantity = (float) $movement->quantity;

        if ($movement->type === 'in') {
            $this->decrementStock($movement->dest_store_id, $movement->product_id, $quantity);
        } elseif ($movement->type === 'out') {
            $this->incrementStock($movement->src_store_id, $movement->product_id, $quantity);
        } elseif ($movement->type === 'transfer') {
            $this->incrementStock($movement->src_store_id, $movement->product_id, $quantity);
            $this->decrementStock($movement->dest_store_id, $movement->product_id, $quantity);
        }
    }

    /**
     * Increment stock for a store-product pair with pessimistic locking.
     * Creates inventory record if it doesn't exist.
     *
     * @param int $storeId
     * @param int $productId
     * @param float $quantity
     * @return void
     */
    private function incrementStock(int $storeId, int $productId, float $quantity): void
    {
        $inventory = Inventory::query()
            ->where('store_id', $storeId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if ($inventory) {
            $inventory->stock += $quantity;
            $inventory->save();
        } else {
            Inventory::query()->create([
                'store_id' => $storeId,
                'product_id' => $productId,
                'stock' => $quantity,
            ]);
        }
    }

    /**
     * Decrement stock for a store-product pair with pessimistic locking.
     * Throws InsufficientStockException if not enough stock.
     *
     * @param int $storeId
     * @param int $productId
     * @param float $quantity
     * @return void
     * @throws InsufficientStockException
     */
    private function decrementStock(int $storeId, int $productId, float $quantity): void
    {
        $inventory = Inventory::query()
            ->where('store_id', $storeId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if (!$inventory || $inventory->stock < $quantity) {
            throw new InsufficientStockException("Store ID {$storeId} does not have enough stock for Product ID {$productId}.");
        }

        $inventory->stock -= $quantity;
        $inventory->save();
    }
}
