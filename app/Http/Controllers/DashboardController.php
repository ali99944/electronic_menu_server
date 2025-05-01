<?php

namespace App\Http\Controllers\Api\V1; // Adjust namespace if needed

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
        // Define grouping format and interval based on period
        $groupByFormat = 'Y-m-d'; // Default for week/month
        $dateInterval = '1 day';
        $chartLabelFormat = 'D j'; // e.g., Sat 16 (Short day name, day number)

        switch ($period) {
             case 'day':
                 $groupByFormat = 'Y-m-d H:00:00'; // Group by hour
                 $dateInterval = '1 hour';
                 $chartLabelFormat = 'ha'; // e.g., 3pm
                 break;
             case 'week': // Default handled below
                $chartLabelFormat = 'D j'; // e.g., Sat 16
                 break;
             case 'month': // Group by week within the month
                 $groupByFormat = 'Y-W'; // Group by Year-Week number
                 $dateInterval = '1 week';
                 $chartLabelFormat = 'W'; // e.g., W11 (Week 11)
                 break;
             case 'year':
                 $groupByFormat = 'Y-m'; // Group by Year-Month
                 $dateInterval = '1 month';
                  $chartLabelFormat = 'M'; // e.g., Mar (Short month name)
                 break;
         }

         // --- Generate Full Date/Time Range ---
         // Create a complete range of dates/hours/weeks/months for the period
         // This ensures charts have points even for periods with zero activity
         $dateRangePoints = [];
         $currentDate = $startDate->copy();
         while ($currentDate <= $endDate) {
             $key = '';
             $label = '';
              switch ($period) {
                 case 'day':
                    $key = $currentDate->format('Y-m-d H:00:00');
                     $label = $currentDate->isoFormat('ha'); // More reliable localized AM/PM
                    break;
                 case 'week':
                    $key = $currentDate->format('Y-m-d');
                     $label = $currentDate->isoFormat('ddd D'); // Localized short day + number
                     break;
                 case 'month':
                     // Key by week number, label as 'Week X'
                    $key = $currentDate->format('Y-W');
                     $label = 'أ' . $currentDate->weekOfYear; // 'أ' for 'أسبوع' (Week)
                     break;
                 case 'year':
                     $key = $currentDate->format('Y-m');
                     $label = $currentDate->isoFormat('MMM'); // Localized short month name
                     break;
             }
             $dateRangePoints[$key] = ['name' => $label, 'value' => 0]; // Initialize with value 0

             // Increment based on interval
             switch ($period) {
                case 'day': $currentDate->addHour(); break;
                case 'week': $currentDate->addDay(); break;
                case 'month': $currentDate->addWeek(); break; // Increment by week for month view
                case 'year': $currentDate->addMonth(); break;
            }
         }


         // --- Fetch Aggregated Data ---
         // TODO: Add ->where('shop_id', $shopId) to these queries

         // Example: Orders Count Trend
         $ordersData = Orders::query()
             ->selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as hour_group,
                           DATE_FORMAT(created_at, '%Y-%m-%d') as day_group,
                           DATE_FORMAT(created_at, '%Y-%u') as week_group, -- Use %u for ISO week starting Monday
                           DATE_FORMAT(created_at, '%Y-%m') as month_group,
                           COUNT(id) as count")
             ->whereBetween('created_at', [$startDate, $endDate])
             ->groupBy(DB::raw($this->getGroupByClause($period))) // Use helper for group by clause
             ->orderBy('created_at') // Order by date to ensure correct sequence
             ->get()
             ->keyBy($this->getGroupByDateKey($period)); // Key results by the group format

        // Example: Sales Amount Trend (using order 'cost_price')
        $salesData = Orders::query()
             ->selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as hour_group,
                           DATE_FORMAT(created_at, '%Y-%m-%d') as day_group,
                           DATE_FORMAT(created_at, '%Y-%u') as week_group,
                           DATE_FORMAT(created_at, '%Y-%m') as month_group,
                           SUM(cost_price) as total")
             ->whereBetween('created_at', [$startDate, $endDate])
             ->groupBy(DB::raw($this->getGroupByClause($period)))
              ->orderBy('created_at')
             ->get()
             ->keyBy($this->getGroupByDateKey($period));

        // --- (Repeat similar queries for Net Income, Delivery Orders, Dine-in Orders, Revenue/Profit) ---
         $deliveryOrdersData = Orders::query()
             ->selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as hour_group, ..., COUNT(id) as count")
             ->where('order_type', 'delivery')
             ->whereBetween('created_at', [$startDate, $endDate])
             ->groupBy(DB::raw($this->getGroupByClause($period)))
             ->orderBy('created_at')
             ->get()->keyBy($this->getGroupByDateKey($period));

         $dineInOrdersData = Orders::query()
             ->selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as hour_group, ..., COUNT(id) as count")
             ->where('order_type', 'inside')
             ->whereBetween('created_at', [$startDate, $endDate])
             ->groupBy(DB::raw($this->getGroupByClause($period)))
             ->orderBy('created_at')
             ->get()->keyBy($this->getGroupByDateKey($period));

         // Simplified Profit Trend (based on dummy calculation, replace with real COGS/Discount data)
         $profitData = Orders::query()
             ->selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as hour_group, ..., SUM(cost_price * 0.60) as total") // Assuming 60% profit margin for simplicity
             ->whereBetween('created_at', [$startDate, $endDate])
             ->groupBy(DB::raw($this->getGroupByClause($period)))
             ->orderBy('created_at')
             ->get()->keyBy($this->getGroupByDateKey($period));


        // --- Merge fetched data with the full date range ---
        $mergedOrders = $dateRangePoints;
        $mergedSales = $dateRangePoints;
        $mergedDelivery = $dateRangePoints;
        $mergedDineIn = $dateRangePoints;
        $mergedProfit = $dateRangePoints;

        foreach ($dateRangePoints as $key => $point) {
            if (isset($ordersData[$key])) $mergedOrders[$key]['value'] = $ordersData[$key]->count;
            if (isset($salesData[$key])) $mergedSales[$key]['value'] = round($salesData[$key]->total, 2);
            if (isset($deliveryOrdersData[$key])) $mergedDelivery[$key]['value'] = $deliveryOrdersData[$key]->count;
            if (isset($dineInOrdersData[$key])) $mergedDineIn[$key]['value'] = $dineInOrdersData[$key]->count;
             if (isset($profitData[$key])) $mergedProfit[$key]['value'] = round($profitData[$key]->total, 2);
            // ... merge data for other charts ...
        }

        // --- Format for Frontend ---
        return [
            'ordersTrend' => ['title' => 'الطلبات', 'data' => array_values($mergedOrders), 'dataKey' => 'value', 'color' => '#6366F1'],
            'salesTrend' => ['title' => 'صافي المبيعات (ر.س)', 'data' => array_values($mergedSales), 'dataKey' => 'value', 'color' => '#10B981'],
            'incomeTrend' => ['title' => 'صافي الدخل (ر.س)', 'data' => [], 'dataKey' => 'value', 'color' => '#F59E0B'], // Placeholder - requires COGS
            'deliveryTrend' => ['title' => 'طلبات التوصيل', 'data' => array_values($mergedDelivery), 'dataKey' => 'value', 'color' => '#3B82F6'],
            // 'pickupTrend' => ['title' => 'طلبات الاستلام', 'data' => [], 'dataKey' => 'value', 'color' => '#8B5CF6'], // Removed
            'dineInTrend' => ['title' => 'الطلبات المحلية', 'data' => array_values($mergedDineIn), 'dataKey' => 'value', 'color' => '#EC4899'],
            // 'discountTrend' => ['title' => 'مبلغ الخصم (ر.س)', 'data' => [], 'dataKey' => 'value', 'color' => '#EF4444'], // Removed
            'revenueTrend' => ['title' => 'مبلغ الأرباح (ر.س)', 'data' => array_values($mergedProfit), 'dataKey' => 'value', 'color' => '#0EA5E9'],
        ];
    }

     /** Helper to get the correct date key based on period for keyBy() */
     private function getGroupByDateKey(string $period): string
     {
         switch($period) {
             case 'day': return 'hour_group';
             case 'week': return 'day_group';
             case 'month': return 'week_group';
             case 'year': return 'month_group';
             default: return 'day_group';
         }
     }

     /** Helper to get the correct GROUP BY clause */
      private function getGroupByClause(string $period): string
      {
         switch($period) {
             case 'day': return 'hour_group';
             case 'week': return 'day_group';
             case 'month': return 'week_group';
             case 'year': return 'month_group';
             default: return 'day_group';
         }
     }

}
