<?php

namespace App\Http\Controllers\Api\V1; // Adjust namespace as needed

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem; // Assuming you have this model
use App\Models\Orders;
use App\Models\Shop; // Assuming Shop/Restaurant model
use Illuminate\Http\Request;
use Illuminate\Support\Carbon; // Use Illuminate Carbon for date manipulation
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    /**
     * Fetch dashboard data based on time period and optional date.
     *
     * @group Dashboard
     * @queryParam period string Time period ('day', 'week', 'month', 'year'). Example: week
     * @queryParam date string Specific date (YYYY-MM-DD) for 'day' period. Example: 2024-03-15
     * @responseFile status=200 scenario="Dashboard Data" storage/responses/dashboard.data.json
     */
    public function index(Request $request)
    {
        // TODO: Authorization - Ensure user can access dashboard for their shop
        // Gate::authorize('viewDashboard', Shop::class); // Or specific permission
        // $shopId = auth()->user()->shop_id; // Get shop ID for filtering

        $validator = Validator::make($request->all(), [
            'period' => 'sometimes|in:day,week,month,year',
            'date' => 'sometimes|required_if:period,day|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $period = $request->input('period', 'week'); // Default to week
        $date = $request->input('date');

        // --- Calculate Date Range ---
        [$startDate, $endDate] = $this->calculateDateRange($period, $date);
        // --- Calculate Previous Period Date Range ---
        [$prevStartDate, $prevEndDate] = $this->calculateDateRange($period, $date, true);


        // --- Fetch Data within Date Range (Add $shopId filter) ---
        $baseQuery = Orders::whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]);
                        // ->where('shop_id', $shopId); // Filter by shop

        // --- Fetch Data for Previous Period ---
        $prevBaseQuery = Orders::whereBetween('created_at', [$prevStartDate->startOfDay(), $prevEndDate->endOfDay()]);
                           // ->where('shop_id', $shopId); // Filter by shop

        // --- Calculate Statistics ---
        // Current Period
        $currentStats = $this->calculateStats($baseQuery);
        // Previous Period (for comparison)
        $previousStats = $this->calculateStats($prevBaseQuery);

        // --- Format Stats with Change % ---
        $stats = $this->formatStatsWithChange($currentStats, $previousStats);

        // --- Prepare Chart Data ---
        $charts = $this->prepareChartData($period, $startDate, $endDate); // Pass $shopId if needed

        // --- Combine Data ---
        $dashboardData = [
            'stats' => $stats,
            'charts' => $charts,
            'filters' => [ // Optionally return the applied filters
                'period' => $period,
                'date' => $date,
                'startDate' => $startDate->toDateString(),
                'endDate' => $endDate->toDateString(),
            ],
        ];

        return response()->json(['data' => $dashboardData]);
    }

    /**
     * Calculate start and end dates based on period and optional date.
     *
     * @param string $period
     * @param string|null $date
     * @param bool $previous Get previous period instead
     * @return array [Carbon $startDate, Carbon $endDate]
     */
    private function calculateDateRange(string $period, ?string $date, bool $previous = false): array
    {
        $targetDate = $date ? Carbon::parse($date) : Carbon::today();

        if ($previous) {
            switch ($period) {
                case 'day':   $targetDate->subDay(); break;
                case 'week':  $targetDate->subWeek(); break;
                case 'month': $targetDate->subMonthNoOverflow(); break;
                case 'year':  $targetDate->subYearNoOverflow(); break;
            }
        }

        switch ($period) {
            case 'day':
                $startDate = $targetDate->copy()->startOfDay();
                $endDate = $targetDate->copy()->endOfDay();
                break;
            case 'month':
                $startDate = $targetDate->copy()->startOfMonth();
                $endDate = $targetDate->copy()->endOfMonth();
                break;
            case 'year':
                $startDate = $targetDate->copy()->startOfYear();
                $endDate = $targetDate->copy()->endOfYear();
                break;
            case 'week':
            default:
                // Ensure week starts on your desired day (e.g., Saturday)
                Carbon::setWeekStartsAt(Carbon::SATURDAY);
                Carbon::setWeekEndsAt(Carbon::FRIDAY);
                $startDate = $targetDate->copy()->startOfWeek();
                $endDate = $targetDate->copy()->endOfWeek();
                break;
        }
        return [$startDate, $endDate];
    }

    /**
     * Calculate key statistics from an Order query builder.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return array
     */
    private function calculateStats(\Illuminate\Database\Eloquent\Builder $query): array
    {
         // Clone query to avoid modifying the original for different calculations
        $totalOrders = $query->clone()->count();
        $completedQuery = $query->clone()->where('status', 'completed'); // Only count completed for sales/revenue

        // NOTE: 'cost_price' in your orders table seems like the *cost* to the restaurant,
        // not the *selling price*. We need the actual sale value. This usually comes
        // from summing order_items prices + tax - discount + delivery.
        // Let's assume you have a `total_amount` or similar column on the `orders` table
        // OR you need to join/sum `order_items`.
        // For simplicity, let's **assume** an 'order_total' column exists on orders.
        // **YOU WILL NEED TO ADJUST THIS BASED ON YOUR ACTUAL PRICE CALCULATION**

        $netSales = $completedQuery->clone()->sum('order_total'); // ASSUMED COLUMN
        // If summing items:
        // $netSales = OrderItem::whereIn('order_id', $completedQuery->pluck('id'))->sum(DB::raw('price * quantity')); // Requires price on OrderItem

        $netIncome = $completedQuery->clone()->sum(DB::raw('order_total - cost_price')); // Revenue = Total - Cost (Simplified)

        $deliveryOrders = $query->clone()->where('order_type', 'delivery')->count();
        // $pickupOrders = $query->clone()->where('order_type', 'pickup')->count(); // If you add pickup type
        $dineInOrders = $query->clone()->where('order_type', 'inside')->count();

        // $totalDiscount = $completedQuery->clone()->sum('discount_amount'); // ASSUMED COLUMN
        $totalRevenue = $netIncome; // Simplified alias for now

        return [
            'totalOrders' => $totalOrders,
            'netSales' => (float) $netSales,
            'netIncome' => (float) $netIncome,
            'deliveryOrders' => $deliveryOrders,
            // 'pickupOrders' => $pickupOrders,
            'dineInOrders' => $dineInOrders,
            // 'totalDiscount' => (float) $totalDiscount,
            'totalRevenue' => (float) $totalRevenue, // Same as netIncome here
             'avgOrderValue' => $totalOrders > 0 ? round($netSales / $totalOrders, 2) : 0,
        ];
    }

     /**
      * Format stats and calculate percentage change from previous period.
      *
      * @param array $currentStats
      * @param array $previousStats
      * @return array
      */
     private function formatStatsWithChange(array $currentStats, array $previousStats): array
     {
        $formatted = [];
        $currencyIcon = 'ر.س'; // Get from config or restaurant setting

         // Define labels and units for clarity
         $statLabels = [
             'totalOrders' => ['label' => 'إجمالي الطلبات', 'unit' => null, 'icon' => 'ShoppingCart'],
             'netSales' => ['label' => 'صافي المبيعات', 'unit' => $currencyIcon, 'icon' => 'BarChartBig'],
             'netIncome' => ['label' => 'صافي الدخل', 'unit' => $currencyIcon, 'icon' => 'HandCoins'], // Using netIncome as basis for Revenue stat too
             'deliveryOrders' => ['label' => 'طلبات التوصيل', 'unit' => null, 'icon' => 'Truck'],
             // 'pickupOrders' => ['label' => 'طلبات الاستلام', 'unit' => null, 'icon' => 'Package'],
             'dineInOrders' => ['label' => 'الطلبات المحلية', 'unit' => null, 'icon' => 'UtensilsCrossed'],
             // 'totalDiscount' => ['label' => 'مبلغ الخصم', 'unit' => $currencyIcon, 'icon' => 'Percent'],
             'totalRevenue' => ['label' => 'مبلغ الأرباح', 'unit' => $currencyIcon, 'icon' => 'TrendingUp'], // Using netIncome as basis
             'avgOrderValue' => ['label' => 'متوسط قيمة الطلب', 'unit' => $currencyIcon, 'icon' => 'DollarSign'],
         ];


         foreach ($statLabels as $key => $details) {
             $currentValue = $currentStats[$key] ?? 0;
             $previousValue = $previousStats[$key] ?? 0;
             $change = null;

             if ($previousValue > 0) {
                 $change = (($currentValue - $previousValue) / $previousValue) * 100;
             } elseif ($currentValue > 0) {
                 $change = 100.0; // Indicate positive change if previous was 0
             } // else change remains null if both are 0

             $formatted[$key] = [
                 'label' => $details['label'],
                 'value' => $details['unit'] ? number_format($currentValue, 2) : $currentValue,
                 'unit' => $details['unit'],
                 'iconName' => $details['icon'], // Send icon name for frontend mapping
                 'change' => $change !== null ? round($change, 1) : null,
             ];
         }
         return $formatted;
     }

     /**
      * Prepare data suitable for chart rendering based on period.
      *
      * @param string $period
      * @param \Illuminate\Support\Carbon $startDate
      * @param \Illuminate\Support\Carbon $endDate
      * @param int|null $shopId // TODO: Add shopId filter
      * @return array
      */
     private function prepareChartData(string $period, Carbon $startDate, Carbon $endDate, ?int $shopId = null): array
     {
         // Base query for completed orders within the range
          $query = Orders::query()
              ->where('status', 'completed')
              // ->where('shop_id', $shopId) // TODO: Add shop filter
              ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]);

         $dateFormat = '';
         $groupBy = '';
         $selectRaw = '';
         $periodLabelPrefix = '';
         $orderBy = 'period_start';

         switch ($period) {
             case 'day':
                // Group by hour
                $dateFormat = '%H:00'; // Hour format (00:00, 01:00...)
                $groupBy = DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')");
                $selectRaw = DB::raw("DATE_FORMAT(created_at, '$dateFormat') as name");
                $periodLabelPrefix = ''; // Label will be like '09:00'
                break;
            case 'week':
                 // Group by day of week (e.g., Sat, Sun...)
                 $dateFormat = '%Y-%m-%d'; // Group by full date
                 $groupBy = DB::raw("DATE(created_at)");
                 // Use database functions for day name - Locale dependent!
                 // Example for MySQL with Arabic locale set: DAYNAME(created_at) or DATE_FORMAT(created_at, '%W')
                 // Safer cross-db approach might be day number or formatting in PHP later
                 $selectRaw = DB::raw("DATE_FORMAT(created_at, '%w') as day_num, DATE_FORMAT(created_at, '%a') as name"); // %w=0 for Sunday, %a=Sun
                 $orderBy = 'day_num';
                 // $periodLabelPrefix = ''; // We'll use the name like 'Sat'
                 break;
            case 'month':
                 // Group by week number within the month
                 $dateFormat = '%Y-%u'; // Year and Week number (ISO 8601)
                 $groupBy = DB::raw("DATE_FORMAT(created_at, '$dateFormat')");
                 // Use WEEK function or similar depending on DB
                 $selectRaw = DB::raw("WEEK(created_at, 1) as week_num, DATE_FORMAT(created_at, '$dateFormat') as name"); // Mode 1 = Week starts Monday
                 $orderBy = 'week_num';
                 $periodLabelPrefix = 'أسبوع ';
                 break;
            case 'year':
                 // Group by month
                 $dateFormat = '%Y-%m'; // Year and Month number
                 $groupBy = DB::raw("DATE_FORMAT(created_at, '$dateFormat')");
                 // Use database functions for month name - Locale dependent!
                 // Example for MySQL with Arabic: DATE_FORMAT(created_at, '%b') or MONTHNAME(created_at)
                 $selectRaw = DB::raw("DATE_FORMAT(created_at, '%Y-%m') as name, MONTH(created_at) as month_num");
                 $orderBy = 'month_num';
                 // $periodLabelPrefix = ''; // Month names are better fetched/mapped
                 break;
            default:
                return []; // Should not happen with validation
         }

        // --- Generate Chart Data ---
         $selectFields = [
             $selectRaw,
             DB::raw('COUNT(*) as totalOrders'),
             DB::raw('SUM(order_total) as netSales'), // ASSUMED COLUMN - ADJUST
             DB::raw('SUM(order_total - cost_price) as netIncome'), // ASSUMED COLUMNS - ADJUST
             DB::raw('SUM(CASE WHEN order_type = "delivery" THEN 1 ELSE 0 END) as deliveryOrders'),
             // DB::raw('SUM(CASE WHEN order_type = "pickup" THEN 1 ELSE 0 END) as pickupOrders'),
             DB::raw('SUM(CASE WHEN order_type = "inside" THEN 1 ELSE 0 END) as dineInOrders'),
              // DB::raw('SUM(discount_amount) as totalDiscount'), // ASSUMED COLUMN
         ];

         $results = $query->clone()
                          ->select($selectFields)
                          ->groupBy($groupBy)
                          ->orderBy($orderBy)
                          ->get()
                          ->keyBy('name'); // Key by the period name (hour, day, week, month) for easy access


         // --- Structure data for frontend charts ---
         // Create a full range of periods (hours, days, weeks, months) to ensure gaps are filled with 0
         $chartDataTemplate = $this->generatePeriodTemplate($period, $startDate, $endDate);

         // Map results to the template
          $mapToTemplate = function($dataKey) use ($results, $chartDataTemplate, $periodLabelPrefix, $period) {
              return $chartDataTemplate->map(function ($templateItem) use ($results, $dataKey, $periodLabelPrefix, $period) {
                  $result = $results->get($templateItem['raw_name']); // Get result for this period
                  $value = $result ? (float) $result->$dataKey : 0; // Use 0 if no result for this period

                  // Customize name display
                  $name = $templateItem['display_name'];
                   if ($period === 'month' && $periodLabelPrefix) {
                       $name = $periodLabelPrefix . $templateItem['week_num']; // Display as 'أسبوع 1', 'أسبوع 2' etc.
                   }

                  return ['name' => $name, 'value' => $value];
              })->values()->toArray(); // Convert back to simple array
          };

         $charts = [
            'ordersTrend' => ['title' => 'الطلبات', 'data' => $mapToTemplate('totalOrders'), 'dataKey' => 'value', 'color' => '#6366F1'],
            'salesTrend' => ['title' => 'صافي المبيعات (ر.س)', 'data' => $mapToTemplate('netSales'), 'dataKey' => 'value', 'color' => '#10B981'],
            'incomeTrend' => ['title' => 'صافي الدخل (ر.س)', 'data' => $mapToTemplate('netIncome'), 'dataKey' => 'value', 'color' => '#F59E0B'],
            'deliveryTrend' => ['title' => 'طلبات التوصيل', 'data' => $mapToTemplate('deliveryOrders'), 'dataKey' => 'value', 'color' => '#3B82F6'],
            // 'pickupTrend' => ['title' => 'طلبات الاستلام', 'data' => $mapToTemplate('pickupOrders'), 'dataKey' => 'value', 'color' => '#8B5CF6'],
            'dineInTrend' => ['title' => 'الطلبات المحلية', 'data' => $mapToTemplate('dineInOrders'), 'dataKey' => 'value', 'color' => '#EC4899'],
            // 'discountTrend' => ['title' => 'مبلغ الخصم (ر.س)', 'data' => $mapToTemplate('totalDiscount'), 'dataKey' => 'value', 'color' => '#EF4444'],
            'revenueTrend' => ['title' => 'مبلغ الأرباح (ر.س)', 'data' => $mapToTemplate('netIncome'), 'dataKey' => 'value', 'color' => '#0EA5E9'], // Using netIncome again for "revenue" chart
         ];

         return $charts;
     }

     /**
     * Generate a template of periods (hours, days, weeks, months) within a date range.
     * This ensures charts have data points for all periods, even if there were no orders.
     */
     private function generatePeriodTemplate(string $period, Carbon $startDate, Carbon $endDate)
     {
         $template = collect();
         $current = $startDate->copy();
         $arabicDays = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
         $arabicMonths = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];


         switch ($period) {
             case 'day':
                 while ($current->lte($endDate)) {
                     $hour = $current->format('H');
                     $template->push(['raw_name' => $hour.':00', 'display_name' => $hour.':00']);
                     $current->addHour();
                 }
                 break;
             case 'week':
                 while ($current->lte($endDate)) {
                     $dayNum = $current->dayOfWeek; // 0 for Sunday
                     $template->push(['raw_name' => $current->format('D'), 'display_name' => $arabicDays[$dayNum]]); // Using short English name for key, Arabic for display
                     $current->addDay();
                 }
                 break;
             case 'month':
                  // Generate week numbers within the month
                  $startWeek = $startDate->weekOfYear;
                  $endWeek = $endDate->weekOfYear;
                  // Handle year transition if month spans across year end
                 if ($endWeek < $startWeek) $endWeek += $startDate->weeksInYear();
                  for ($w = $startWeek; $w <= $endWeek; $w++) {
                     // Key needs year + week for uniqueness across years if range spans years
                      $yearOfWeek = ($w > $startDate->weeksInYear()) ? $startDate->year + 1 : $startDate->year;
                      $weekNumInYear = ($w > $startDate->weeksInYear()) ? $w - $startDate->weeksInYear() : $w;
                      $template->push([
                          'raw_name' => $yearOfWeek . '-' . sprintf('%02d', $weekNumInYear), // ISO week format YYYY-WW
                          'week_num' => $weekNumInYear, // For display label prefix
                           'display_name' => 'Week ' . $weekNumInYear // Placeholder, label constructed in mapToTemplate
                       ]);
                   }
                 break;
             case 'year':
                 while ($current->lte($endDate)) {
                     $monthNum = $current->month - 1; // 0-indexed
                     $template->push(['raw_name' => $current->format('Y-m'), 'display_name' => $arabicMonths[$monthNum]]);
                     $current->addMonthNoOverflow();
                 }
                 break;
         }

         return $template;
     }

}
