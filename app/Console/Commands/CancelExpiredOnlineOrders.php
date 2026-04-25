<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\StockMovement;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CancelExpiredOnlineOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:cancel-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel unpaid online orders older than 24 hours and revert their stock.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredOrders = Order::query()
            ->where('type', 'online')
            ->where('payment_status', 'unpaid')
            ->where('created_at', '<', Carbon::now()->subHours(24))
            ->with('items')
            ->get();

        foreach ($expiredOrders as $order) {
            DB::transaction(function () use ($order) {
                $order->update(['status' => 'cancelled']);

                foreach ($order->items as $item) {
                    $inventory = Inventory::query()
                        ->where('store_id', $order->store_id)
                        ->where('product_id', $item->product_id)
                        ->first();

                    if ($inventory) {
                        $inventory->increment('stock', $item->quantity);

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
                }
            });
            $this->info("Cancelled order: {$order->order_number}");
        }
    }
}
