<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalesDashboardService
{
    public function summary(?int $storeId = null): array
    {
        return [
            'daily' => $this->buildPeriodSummary(
                $storeId,
                Carbon::today()->startOfDay(),
                Carbon::today()->endOfDay(),
                Carbon::yesterday()->startOfDay(),
                Carbon::yesterday()->endOfDay(),
            ),
            'weekly' => $this->buildPeriodSummary(
                $storeId,
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
                Carbon::now()->subWeek()->startOfWeek(),
                Carbon::now()->subWeek()->endOfWeek(),
            ),
            'monthly' => $this->buildPeriodSummary(
                $storeId,
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
                Carbon::now()->subMonthNoOverflow()->startOfMonth(),
                Carbon::now()->subMonthNoOverflow()->endOfMonth(),
            ),
            'chart' => [
                'daily' => $this->buildTimeSeries($storeId, 7, 'day'),
                'weekly' => $this->buildTimeSeries($storeId, 7, 'week'),
                'monthly' => $this->buildTimeSeries($storeId, 6, 'month'),
            ],
            'recent_transactions' => $this->buildRecentTransactions($storeId),
            'top_debtors' => $this->buildTopDebtors($storeId),
        ];
    }

    private function buildPeriodSummary(
        ?int $storeId,
        Carbon $currentStart,
        Carbon $currentEnd,
        Carbon $previousStart,
        Carbon $previousEnd,
    ): array {
        $current = $this->buildRangeSummary($storeId, $currentStart, $currentEnd);
        $previous = $this->buildRangeSummary($storeId, $previousStart, $previousEnd);

        return [
            'total_omzet' => $current['total_omzet'],
            'total_omzet_change' => $this->percentageChange($current['total_omzet'], $previous['total_omzet']),
            'total_transactions' => $current['total_transactions'],
            'total_transactions_change' => $this->percentageChange($current['total_transactions'], $previous['total_transactions']),
            'products_sold' => $current['products_sold'],
            'products_sold_change' => $this->percentageChange($current['products_sold'], $previous['products_sold']),
            'average_transaction_amount' => $current['average_transaction_amount'],
            'average_transaction_amount_change' => $this->percentageChange($current['average_transaction_amount'], $previous['average_transaction_amount']),
        ];
    }

    private function percentageChange(float $current, float $previous): float
    {
        if ($previous === 0.0) {
            return $current === 0.0 ? 0.0 : 100.0;
        }

        return round((($current - $previous) / abs($previous)) * 100, 2);
    }

    private function buildTimeSeries(?int $storeId, int $points, string $interval): array
    {
        $labels = [];
        $omzet = [];

        for ($i = $points - 1; $i >= 0; $i--) {
            if ($interval === 'day') {
                $start = Carbon::today()->subDays($i)->startOfDay();
                $end = Carbon::today()->subDays($i)->endOfDay();
                $labels[] = $start->format('d M');
            } elseif ($interval === 'week') {
                $start = Carbon::now()->subWeeks($i)->startOfWeek();
                $end = Carbon::now()->subWeeks($i)->endOfWeek();
                $labels[] = 'Wk ' . $start->weekOfYear;
            } else {
                $start = Carbon::now()->subMonthsNoOverflow($i)->startOfMonth();
                $end = Carbon::now()->subMonthsNoOverflow($i)->endOfMonth();
                $labels[] = $start->format('M');
            }

            $summary = $this->buildRangeSummary($storeId, $start, $end);

            $omzet[] = $summary['total_omzet'];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'name' => 'total_omzet',
                    'data' => $omzet,
                ],
            ],
        ];
    }

    private function buildRecentTransactions(?int $storeId): array
    {
        return Order::query()
            ->when($storeId, fn ($query) => $query->where('store_id', $storeId))
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn (Order $order) => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'type' => $order->type,
                'total_amount' => (float) $order->total_amount,
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'status' => $order->status,
                'created_at' => $order->created_at->toDateTimeString(),
            ])
            ->toArray();
    }

    private function buildTopDebtors(?int $storeId): array
    {
        $paymentTotals = Payment::query()
            ->select('order_id', DB::raw('SUM(amount) as paid_amount'))
            ->groupBy('order_id');

        return Order::query()
            ->select([
                'customers.id as customer_id',
                'customers.name as customer_name',
                'customers.email as customer_email',
                'customers.phone as customer_phone',
                DB::raw('SUM(orders.total_amount - COALESCE(payment_totals.paid_amount, 0)) as total_due'),
                DB::raw('COUNT(orders.id) as order_count'),
            ])
            ->when($storeId, fn ($query) => $query->where('orders.store_id', $storeId))
            ->whereNotNull('orders.customer_id')
            ->whereIn('orders.payment_status', ['unpaid', 'partial'])
            ->leftJoinSub($paymentTotals, 'payment_totals', 'orders.id', '=', 'payment_totals.order_id')
            ->join('users as customers', 'customers.id', '=', 'orders.customer_id')
            ->groupBy('customers.id', 'customers.name', 'customers.email', 'customers.phone')
            ->havingRaw('SUM(orders.total_amount - COALESCE(payment_totals.paid_amount, 0)) > 0')
            ->orderByDesc('total_due')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'customer_id' => $row->customer_id,
                'customer_name' => $row->customer_name,
                'customer_email' => $row->customer_email,
                'customer_phone' => $row->customer_phone,
                'total_due' => (float) $row->total_due,
                'order_count' => (int) $row->order_count,
            ])
            ->toArray();
    }

    private function buildRangeSummary(?int $storeId, Carbon $start, Carbon $end): array
    {
        $orderQuery = Order::query()
            ->when($storeId, fn ($query) => $query->where('store_id', $storeId))
            ->whereBetween('created_at', [$start, $end]);

        $totalAmount = (float) $orderQuery->sum('total_amount');
        $totalTransactions = $orderQuery->count();

        $productsSold = (int) OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->when($storeId, fn ($query) => $query->where('orders.store_id', $storeId))
            ->whereBetween('orders.created_at', [$start, $end])
            ->sum('order_items.quantity');

        return [
            'total_omzet' => round($totalAmount, 2),
            'total_transactions' => $totalTransactions,
            'products_sold' => $productsSold,
            'average_transaction_amount' => $totalTransactions > 0
                ? round($totalAmount / $totalTransactions, 2)
                : 0,
        ];
    }
}
