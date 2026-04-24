<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

/**
 * Reusable service for creating stock movements and auto-updating inventory.
 *
 * This service is designed to be used by multiple features:
 * - Manual stock movements (StockMovementController)
 * - Purchase orders (coming soon)
 * - Sales orders (coming soon)
 */
class StockMovementService
{
    /**
     * Create a stock movement and auto-adjust inventory stock.
     *
     * @param array{
     *     src_store_id: int,
     *     dest_store_id: int,
     *     product_id: int,
     *     quantity: float,
     *     type: string,
     *     title: string,
     *     note?: string|null,
     * } $data
     * @return StockMovement
     */
    public function create(array $data): StockMovement
    {
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
     * Adjust inventory stock based on movement type.
     *
     * - type "in": increase stock at dest_store, decrease at src_store
     * - type "out": decrease stock at dest_store, increase at src_store
     *
     * @param StockMovement $movement
     * @return void
     */
    private function adjustInventory(StockMovement $movement): void
    {
        $quantity = (float) $movement->quantity;

        if ($movement->type === 'in') {
            // Stock masuk ke destination store
            $this->incrementStock($movement->dest_store_id, $movement->product_id, $quantity);
            // Stock keluar dari source store
            $this->decrementStock($movement->src_store_id, $movement->product_id, $quantity);
        } else {
            // type "out": Stock keluar dari destination store
            $this->decrementStock($movement->dest_store_id, $movement->product_id, $quantity);
            // Stock masuk ke source store
            $this->incrementStock($movement->src_store_id, $movement->product_id, $quantity);
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
            // Reverse: decrease dest, increase src
            $this->decrementStock($movement->dest_store_id, $movement->product_id, $quantity);
            $this->incrementStock($movement->src_store_id, $movement->product_id, $quantity);
        } else {
            // Reverse out: increase dest, decrease src
            $this->incrementStock($movement->dest_store_id, $movement->product_id, $quantity);
            $this->decrementStock($movement->src_store_id, $movement->product_id, $quantity);
        }
    }

    /**
     * Increment stock for a store-product pair.
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
            ->first();

        if ($inventory) {
            $inventory->increment('stock', $quantity);
        } else {
            Inventory::query()->create([
                'store_id' => $storeId,
                'product_id' => $productId,
                'stock' => $quantity,
            ]);
        }
    }

    /**
     * Decrement stock for a store-product pair.
     * Does nothing if inventory record doesn't exist.
     *
     * @param int $storeId
     * @param int $productId
     * @param float $quantity
     * @return void
     */
    private function decrementStock(int $storeId, int $productId, float $quantity): void
    {
        $inventory = Inventory::query()
            ->where('store_id', $storeId)
            ->where('product_id', $productId)
            ->first();

        if ($inventory) {
            $inventory->decrement('stock', $quantity);
        }
    }
}
