<?php

namespace App\Console\Commands;

use App\Models\CartItem;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ClearAbandonedCarts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'carts:clear-abandoned {--days=7 : Delete carts not updated after this many days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear abandoned carts based on the last update timestamp.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = max((int) $this->option('days'), 1);
        $threshold = Carbon::now()->subDays($days);

        $deleted = CartItem::query()
            ->where('updated_at', '<', $threshold)
            ->delete();

        $this->info("Cleared {$deleted} abandoned cart items older than {$days} day(s).");

        return self::SUCCESS;
    }
}
