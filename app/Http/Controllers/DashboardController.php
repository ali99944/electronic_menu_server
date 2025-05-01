<?php

namespace App\Http\Controllers; // Adjust namespace if needed

use App\Http\Controllers\Controller;
use App\Models\Orders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Use DB facade for aggregate queries
use App\Models\Order; // Assuming Order model exists
use App\Models\OrderItem; // Assuming OrderItem model exists
use Carbon\Carbon; // For date manipulation

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics and chart data based on a time period.
     * @group Dashboard
     * @queryParam period string Time period ('day', 'week', 'month', 'year'). Defaults to 'week'. Example: month
     * @queryParam date string Specific date for 'day' period (YYYY-MM-DD). Example: 2024-03-15
     * @responseFile status=200 scenario="Dashboard Data" storage/responses/dashboard.data.json
     */
    public function index(Request $request)
    {
        // TODO: Authorization - Ensure user can access dashboard for their shop
        // Gate::authorize('viewDashboard', Shop::class); // Example Policy
        // TODO: Get shop_id based on authenticated user
        // $shopId = auth()->user()->shop_id;

        $period = $request->query('period', 'week'); // Default to week
        $date = $request->query('date'); // Specific date for 'day' period

        // --- Calculate Date Range ---
        $dateRange = $this->calculateDateRange($period, $date);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        // --- Fetch & Calculate Stats ---
        // TODO: Filter all queries below by shop_id
        $stats = $this->calculateStats($startDate, $endDate);

        // --- Fetch & Format Chart Data ---
        // TODO: Filter all queries below by shop_id
        $charts = $this->generateChartData($period, $startDate, $endDate);


        // --- Combine Data ---
        $dashboardData = [
            'stats' => $stats,
            'charts' => $charts,
            'filters' => [ // Return current filters for context
                'period' => $period,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ]
        ];

        return response()->json(['data' => $dashboardData]);
    }

    // --- Helper Functions ---

    /**
     * Calculate the start and end dates based on the period.
     */
    protected function calculateDateRange(string $period, ?string $date = null): array
    {
        $targetDate = $date ? Carbon::parse($date)->endOfDay() : now()->endOfDay(); // Use provided date or today
        $startDate = now(); // Initialize
        $endDate = $targetDate; // Default end date

        switch ($period) {
            case 'day':
                $startDate = $targetDate->copy()->startOfDay();
                break;
            case 'week':
                // Adjust locale/start of week if needed (e.g., Carbon::setLocale('ar'), startOfWeek(Carbon::SATURDAY))
                $startDate = $targetDate->copy()->startOfWeek();
                $endDate = $targetDate->copy()->endOfWeek();
                break;
            case 'month':
                $startDate = $targetDate->copy()->startOfMonth();
                $endDate = $targetDate->copy()->endOfMonth();
                break;
            case 'year':
                $startDate = $targetDate->copy()->startOfYear();
                $endDate = $targetDate->copy()->endOfYear();
                break;
            default: // Default to week
                $startDate = $targetDate->copy()->startOfWeek();
                 $endDate = $targetDate->copy()->endOfWeek();
                break;
        }

        return ['start' => $startDate, 'end' => $endDate];
    }

    /**
     * Calculate summary statistics for the given date range.
     */
    protected function calculateStats(Carbon $startDate, Carbon $endDate): array
    {
        // Base Query for Orders in the period
        // TODO: Add ->where('shop_id', $shopId) to this query
        $ordersQuery = Orders::query()->whereBetween('created_at', [$startDate, $endDate]);

        // --- Calculate Raw Stats ---
        $totalOrders = $ordersQuery->count();
        // Assuming 'cost_price' in orders table represents the GRAND TOTAL of the order after discounts/taxes etc.
        // If not, you need to calculate it by summing order_items prices + tax - discounts etc.
        $totalSales = $ordersQuery->sum('cost_price'); // Use the final price field
        // Clone query to avoid modifying the original for different calculations
        $deliveryOrdersCount = $ordersQuery->clone()->where('order_type', 'delivery')->count();
        // $pickupOrdersCount = $ordersQuery->clone()->where('order_type', 'pickup')->count(); // Removed based on request
        $dineInOrdersCount = $ordersQuery->clone()->where('order_type', 'inside')->count();

        // Calculate Average Order Value (handle division by zero)
        $averageOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;

        // --- Calculate Net Sales & Income (Requires more data) ---
        // Net Sales: Usually Total Sales - Discounts
        // Net Income/Profit: Usually Net Sales - Cost of Goods Sold (COGS)
        // These require fetching discount amounts and potentially item costs.
        // For this example, we'll make assumptions or use placeholders.

        // Placeholder Calculation (Requires Discount/Cost data on Order or Items)
        // Example: Fetch total discount applied within the period
        // $totalDiscount = $ordersQuery->clone()->sum('discount_amount'); // Assuming discount_amount column exists
        $totalDiscount = $totalSales * 0.05; // Dummy 5% discount average

        // Example: Fetch total cost of goods sold (COGS)
        // $totalCOGS = OrderItem::query()
        //      ->whereHas('order', fn($q) => $q->whereBetween('created_at', [$startDate, $endDate])) // Filter by order date
        //      ->sum(DB::raw('quantity * cost_price')); // Assuming cost_price column exists on OrderItem
        $totalCOGS = $totalSales * 0.35; // Dummy 35% COGS average

        $netSales = $totalSales - $totalDiscount;
        $netIncome = $netSales - $totalCOGS; // Simplified Profit


        // --- TODO: Calculate Change vs Previous Period ---
        // For change %, you need to fetch the same stats for the *previous* period
        // e.g., if period is 'week', fetch for the week before $startDate
        // $previousStartDate = $startDate->copy()->subWeek(); ... fetch stats ... calculate % diff


        // --- Format Stats Array ---
        return [
            'totalOrders' => ['label' => 'إجمالي الطلبات', 'value' => $totalOrders, /* 'change' => ... */ ],
            'netSales' => ['label' => 'صافي المبيعات', 'value' => number_format($netSales, 2), 'unit' => 'ر.س', /* 'change' => ... */ ],
            'netIncome' => ['label' => 'صافي الدخل (تقديري)', 'value' => number_format($netIncome, 2), 'unit' => 'ر.س', /* 'change' => ... */ ],
            'deliveryOrders' => ['label' => 'طلبات التوصيل', 'value' => $deliveryOrdersCount, /* 'change' => ... */ ],
            // 'pickupOrders' => ['label' => 'طلبات الاستلام', 'value' => $pickupOrdersCount, /* 'change' => ... */ ], // Removed
            'dineInOrders' => ['label' => 'الطلبات المحلية', 'value' => $dineInOrdersCount, /* 'change' => ... */ ],
            // 'totalDiscount' => ['label' => 'مبلغ الخصم', 'value' => number_format($totalDiscount, 2), 'unit' => 'ر.س'], // Removed based on request
            'totalRevenue' => ['label' => 'مبلغ الأرباح (تقديري)', 'value' => number_format($netIncome, 2), 'unit' => 'ر.س'], // Often same as Net Income/Profit
            'averageOrderValue' => ['label' => 'متوسط قيمة الطلب', 'value' => number_format($averageOrderValue, 2), 'unit' => 'ر.س', /* 'change' => ... */ ],
            // 'newCustomers' => ['label' => 'عملاء جدد', 'value' => 'N/A' /* Calculate based on customer creation date */ ], // Removed based on request
        ];
    }


    /**
     * Generate data formatted for the area charts.
     */
    protected function generateChartData(string $period, Carbon $startDate, Carbon $endDate): array
    {
        // --- Determine Grouping Format and Label ---
        $groupByClause = $this->getGroupByClause($period);
        $dateSelectExpression = $this->getDateSelectExpression($period, $groupByClause); // Get the SELECT expression for the date group
        $chartLabelFormat = ''; // We'll generate labels below

        // --- Generate Full Date/Time Range (Keep this logic) ---
        $dateRangePoints = [];
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $key = '';
            $label = '';
            switch ($period) {
                case 'day':
                    $key = $currentDate->format('Y-m-d H:00:00');
                    $label = $currentDate->isoFormat('ha');
                    break;
                case 'week':
                    $key = $currentDate->format('Y-m-d');
                    $label = $currentDate->isoFormat('ddd D');
                    break;
                case 'month':
                    $key = $currentDate->format('Y-W'); // Group by Year-Week
                    $label = 'أ' . $currentDate->weekOfYear;
                    break;
                case 'year':
                    $key = $currentDate->format('Y-m');
                    $label = $currentDate->isoFormat('MMM');
                    break;
                default: // Default to week
                    $key = $currentDate->format('Y-m-d');
                    $label = $currentDate->isoFormat('ddd D');
                    break;
            }
             // Ensure unique keys if week/month spans year boundary slightly differently than DATE_FORMAT
             // This simple keying might need refinement for month/week grouping accuracy
             $uniqueKey = $key;
             if (!isset($dateRangePoints[$uniqueKey])) { // Avoid duplicates if loop logic causes overlap
                $dateRangePoints[$uniqueKey] = ['name' => $label, 'value' => 0];
             }


            // Increment based on interval
            switch ($period) {
                case 'day': $currentDate->addHour(); break;
                case 'week': $currentDate->addDay(); break;
                case 'month': $currentDate->addWeek(); break;
                case 'year': $currentDate->addMonth(); break;
                default: $currentDate->addDay(); break;
            }
            // Safety break to prevent infinite loops if interval logic has issues
             if(count($dateRangePoints) > 366 && $period !== 'day') break;
             if(count($dateRangePoints) > 24*7 && $period === 'day') break; // Limit hourly points too
        }


        // --- Fetch Aggregated Data (Corrected SELECT) ---
        // TODO: Add ->where('shop_id', $shopId) to these queries

        // Example: Orders Count Trend
        $ordersData = Orders::query()
            ->selectRaw("{$dateSelectExpression} as date_group, COUNT(id) as count") // Select ONLY the group alias and the count
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date_group') // Group by the alias
            ->orderBy('date_group') // Order by the alias
            ->get()
            ->keyBy('date_group'); // Key results by the alias

        // Example: Sales Amount Trend
        $salesData = Orders::query()
            ->selectRaw("{$dateSelectExpression} as date_group, SUM(cost_price) as total") // Select ONLY the group alias and the sum
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date_group')
            ->orderBy('date_group')
            ->get()
            ->keyBy('date_group');

        // Example: Delivery Orders Trend
        $deliveryOrdersData = Orders::query()
            ->selectRaw("{$dateSelectExpression} as date_group, COUNT(id) as count")
            ->where('order_type', 'delivery')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date_group')
            ->orderBy('date_group')
            ->get()->keyBy('date_group');

        // Example: Dine-in Orders Trend
        $dineInOrdersData = Orders::query()
             ->selectRaw("{$dateSelectExpression} as date_group, COUNT(id) as count")
             ->where('order_type', 'inside')
             ->whereBetween('created_at', [$startDate, $endDate])
             ->groupBy('date_group')
             ->orderBy('date_group')
             ->get()->keyBy('date_group');

        // Example: Simplified Profit Trend
        $profitData = Orders::query()
             ->selectRaw("{$dateSelectExpression} as date_group, SUM(cost_price * 0.60) as total") // Placeholder calculation
             ->whereBetween('created_at', [$startDate, $endDate])
             ->groupBy('date_group')
             ->orderBy('date_group')
             ->get()->keyBy('date_group');


        // --- Merge fetched data with the full date range ---
        $mergedOrders = $dateRangePoints;
        $mergedSales = $dateRangePoints;
        $mergedDelivery = $dateRangePoints;
        $mergedDineIn = $dateRangePoints;
        $mergedProfit = $dateRangePoints;

        // Loop through the fetched data and update the corresponding point in the full range
        foreach ($ordersData as $key => $data) { if(isset($mergedOrders[$key])) $mergedOrders[$key]['value'] = $data->count; }
        foreach ($salesData as $key => $data) { if(isset($mergedSales[$key])) $mergedSales[$key]['value'] = round($data->total, 2); }
        foreach ($deliveryOrdersData as $key => $data) { if(isset($mergedDelivery[$key])) $mergedDelivery[$key]['value'] = $data->count; }
        foreach ($dineInOrdersData as $key => $data) { if(isset($mergedDineIn[$key])) $mergedDineIn[$key]['value'] = $data->count; }
        foreach ($profitData as $key => $data) { if(isset($mergedProfit[$key])) $mergedProfit[$key]['value'] = round($data->total, 2); }
        // ... merge data for other charts ...


        // --- Format for Frontend ---
        return [
            'ordersTrend' => ['title' => 'الطلبات', 'data' => array_values($mergedOrders), 'dataKey' => 'value', 'color' => '#6366F1'],
            'salesTrend' => ['title' => 'صافي المبيعات (ر.س)', 'data' => array_values($mergedSales), 'dataKey' => 'value', 'color' => '#10B981'],
            // 'incomeTrend' => ['title' => 'صافي الدخل (ر.س)', 'data' => [], 'dataKey' => 'value', 'color' => '#F59E0B'], // Removed based on previous request
            'deliveryTrend' => ['title' => 'طلبات التوصيل', 'data' => array_values($mergedDelivery), 'dataKey' => 'value', 'color' => '#3B82F6'],
            'dineInTrend' => ['title' => 'الطلبات المحلية', 'data' => array_values($mergedDineIn), 'dataKey' => 'value', 'color' => '#EC4899'],
            'revenueTrend' => ['title' => 'مبلغ الأرباح (ر.س)', 'data' => array_values($mergedProfit), 'dataKey' => 'value', 'color' => '#0EA5E9'],
             // Removed discount, pickup trends based on previous request
        ];
    }

    /** Helper to get the correct SQL expression for date grouping */
    private function getDateSelectExpression(string $period, string $alias = 'date_group'): string
    {
        return match ($period) {
            'day' => "DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as {$alias}",
            'week' => "DATE_FORMAT(created_at, '%Y-%m-%d') as {$alias}",
            'month' => "DATE_FORMAT(created_at, '%Y-%u') as {$alias}", // ISO week number
            'year' => "DATE_FORMAT(created_at, '%Y-%m') as {$alias}",
            default => "DATE_FORMAT(created_at, '%Y-%m-%d') as {$alias}", // Default week (daily)
        };
    }

    /** Helper to get the correct GROUP BY clause */
     private function getGroupByClause(string $period): string
     {
         // Group by the same expression used in SELECT
         return $this->getDateSelectExpression($period);
     }
}
