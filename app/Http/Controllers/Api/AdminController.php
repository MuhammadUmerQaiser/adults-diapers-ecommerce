<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    protected $user;
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getAllUsers(Request $request)
    {
        return $this->user->getAllUsers($request);
    }

    public function updateUser(UpdateUserRequest $request, int $id)
    {
        return $this->user->updateUser($request, $id);
    }

    public function deleteUser(int $id)
    {
        return $this->user->deleteUser($id);
    }

    public function getAllCustomers(Request $request)
    {
        return $this->user->getAllCustomers($request);
    }

    public function dashboard()
    {
        try {
            // ── KPI Cards ──────────────────────────────────────────
            $totalRevenue = Order::where('payment_status', 'paid')->sum('total');
            $totalOrders = Order::count();
            $totalCustomers = User::where('role_id', 3)->count();
            $avgOrderValue = $totalOrders > 0
                ? round($totalRevenue / $totalOrders, 2)
                : 0;

            // Month-over-month deltas (current vs previous calendar month)
            $now = now();
            $curStart = $now->copy()->startOfMonth();
            $prevStart = $now->copy()->subMonth()->startOfMonth();
            $prevEnd = $now->copy()->subMonth()->endOfMonth();

            $revenueThisMonth = Order::where('payment_status', 'paid')->whereBetween('created_at', [$curStart, $now])->sum('total');
            $revenuePrevMonth = Order::where('payment_status', 'paid')->whereBetween('created_at', [$prevStart, $prevEnd])->sum('total');
            $ordersThisMonth = Order::whereBetween('created_at', [$curStart, $now])->count();
            $ordersPrevMonth = Order::whereBetween('created_at', [$prevStart, $prevEnd])->count();
            $custThisMonth = User::where('role_id', 3)->whereBetween('created_at', [$curStart, $now])->count();
            $custPrevMonth = User::where('role_id', 3)->whereBetween('created_at', [$prevStart, $prevEnd])->count();
            $avgThis = $ordersThisMonth > 0 ? round($revenueThisMonth / $ordersThisMonth, 2) : 0;
            $avgPrev = $ordersPrevMonth > 0 ? round($revenuePrevMonth / $ordersPrevMonth, 2) : 0;

            $pct = function ($cur, $prev) {
                if ($prev == 0 && $cur == 0)
                    return null;
                if ($prev == 0)
                    return null;
                return round((($cur - $prev) / $prev) * 100, 1);
            };

            $fmt = function ($change) {
                if ($change === null)
                    return null;
                if ($change <= 0)
                    return "0%";
                return "+{$change}%";
            };

            // ── Revenue & Orders Trend (last 6 months) ─────────────
            $trend = Order::select(
                DB::raw("DATE_FORMAT(created_at, '%b') as month"),
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month_key"),
                DB::raw("SUM(CASE WHEN payment_status = 'paid' THEN total ELSE 0 END) as revenue"),
                DB::raw("COUNT(*) as orders")
            )
                ->where('created_at', '>=', $now->copy()->subMonths(5)->startOfMonth())
                ->groupBy('month_key', 'month')
                ->orderBy('month_key')
                ->get();

            // ── Order Status Distribution ──────────────────────────
            $statusCounts = Order::select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as total'))
                ->groupBy('status')
                ->get()
                ->keyBy('status');

            $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
            $orderStatus = collect($statuses)->map(fn($s) => [
                'status' => $s,
                'count' => (int) ($statusCounts[$s]->count ?? 0),
                'total' => round((float) ($statusCounts[$s]->total ?? 0), 2),
            ]);

            // ── Top Products (by revenue) ──────────────────────────
            $topProducts = DB::table('order_details')
                ->join('orders', 'orders.id', '=', 'order_details.order_id')
                ->join('product_variations', 'product_variations.id', '=', 'order_details.product_variation_id')
                ->where('orders.payment_status', 'paid')
                ->select(
                    'order_details.product_variation_id',
                    'order_details.product_name',
                    'order_details.sku',
                    DB::raw('SUM(order_details.quantity) as units_sold'),
                    DB::raw('SUM(order_details.subtotal) as revenue'),
                    'product_variations.stock',
                    'product_variations.price'
                )
                ->groupBy(
                    'order_details.product_variation_id',
                    'order_details.product_name',
                    'order_details.sku',
                    'product_variations.stock',
                    'product_variations.price'
                )
                ->orderByDesc('revenue')
                ->limit(5)
                ->get();

            return api_success([
                'kpis' => [
                    'total_revenue' => [
                        'value' => round((float) $totalRevenue, 2),
                        'change' => $fmt($pct($revenueThisMonth, $revenuePrevMonth)),
                    ],
                    'total_orders' => [
                        'value' => $totalOrders,
                        'change' => $fmt($pct($ordersThisMonth, $ordersPrevMonth)),
                    ],
                    'total_customers' => [
                        'value' => $totalCustomers,
                        'change' => $fmt($pct($custThisMonth, $custPrevMonth)),
                    ],
                    'avg_order_value' => [
                        'value' => $avgOrderValue,
                        'change' => $fmt($pct($avgThis, $avgPrev)),
                    ],
                ],
                'revenue_trend' => $trend,
                'order_status' => $orderStatus,
                'top_products' => $topProducts,
            ], 'Dashboard data retrieved successfully.');

        } catch (\Exception $e) {
            return api_error('Something went wrong while loading dashboard.', 500, $e->getMessage());
        }
    }
}
