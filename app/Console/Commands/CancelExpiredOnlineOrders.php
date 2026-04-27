<?php

namespace App\Console\Commands;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CancelExpiredOnlineOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:cancel-expired {--days=3 : Cancel online orders older than this many days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel expired pending/processing online orders and revert their stock.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = max((int) $this->option('days'), 1);

        $expiredOrderIds = Order::query()
            ->where('type', 'online')
            ->where('payment_status', 'unpaid')
            ->whereIn('status', ['pending', 'processing'])
            ->where('created_at', '<', Carbon::now()->subDays($days))
            ->pluck('id');

        $cancelledCount = 0;

        foreach ($expiredOrderIds as $orderId) {
            DB::transaction(function () use ($orderId, &$cancelledCount) {
                $order = Order::query()
                    ->whereKey($orderId)
                    ->where('type', 'online')
                    ->where('payment_status', 'unpaid')
                    ->whereIn('status', ['pending', 'processing'])
                    ->lockForUpdate()
                    ->with('items')
                    ->first();

                if (! $order) {
                    return;
                }

                $order->update(['status' => 'cancelled']);

                foreach ($order->items as $item) {
                    $inventory = Inventory::query()
                        ->where('store_id', $order->store_id)
                        ->where('product_id', $item->product_id)
                        ->lockForUpdate()
                        ->first();

                    if ($inventory) {
                        $inventory->increment('stock', $item->quantity);
                    } else {
                        Inventory::query()->create([
                            'store_id' => $order->store_id,
                            'product_id' => $item->product_id,
                            'stock' => $item->quantity,
                            'purchase_price' => 0,
                            'selling_price' => 0,
                            'discount_percentage' => 0,
                            'min_stock' => 0,
                            'is_active' => true,
                        ]);
                    }

                    StockMovement::create([
                        'src_store_id' => null,
                        'dest_store_id' => $order->store_id,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'type' => 'in',
                        'title' => 'Order ' . $order->order_number . ' Cancelled',
                        'note' => 'Auto-cancel expired order',
                    ]);
                }

                $cancelledCount++;
                $this->info("Cancelled order: {$order->order_number}");
            });
        }

        $this->info("Total cancelled orders: {$cancelledCount}");

        return self::SUCCESS;
    }
}
