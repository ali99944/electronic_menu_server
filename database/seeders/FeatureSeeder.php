<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FeatureCategory;
use App\Models\Feature;
use Illuminate\Support\Facades\DB;

class FeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data to avoid duplicates if run multiple times
        DB::statement('SET FOREIGN_KEY_CHECKS=0;'); // Disable FK constraints temporarily
        Feature::truncate();
        FeatureCategory::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;'); // Re-enable FK constraints


        // --- Data Definition (Matches frontend example) ---
        $featureGroups = [
            [
                'title' => "إدارة القائمة (المنيو)",
                'icon_name' => "ListChecks", // Use Lucide icon names
                'display_order' => 1,
                'features' => [
                    ['name' => "إدارة التصنيفات", 'description' => "إنشاء وحذف تصنيفات الأطباق.", 'icon_name' => "Tag", 'available_in_base' => true, 'display_order' => 1],
                    ['name' => "إدارة الأطباق", 'description' => "إضافة وتعديل وحذف الأطباق.", 'icon_name' => "Coffee", 'available_in_base' => true, 'display_order' => 2],
                    ['name' => "أنواع وأسعار متعددة", 'description' => "تحديد أسعار مختلفة لنفس الطبق.", 'icon_name' => "CheckCircle", 'available_in_base' => true, 'display_order' => 3],
                    ['name' => "تحميل صور الأطباق", 'description' => "إضافة صور عالية الجودة لكل طبق.", 'icon_name' => "CheckCircle", 'available_in_base' => true, 'display_order' => 4],
                ]
            ],
            [
                'title' => "إدارة الطلبات",
                'icon_name' => "ShoppingBasket",
                'display_order' => 2,
                'features' => [
                    ['name' => "استقبال الطلبات الجديدة", 'description' => "عرض الطلبات الواردة في الوقت الفعلي.", 'icon_name' => "CheckCircle", 'available_in_base' => true, 'display_order' => 1],
                    ['name' => "عرض تفاصيل الطلبات", 'description' => "مشاهدة تفاصيل كل طلب.", 'icon_name' => "CheckCircle", 'available_in_base' => true, 'display_order' => 2],
                    ['name' => "تغيير حالة الطلب", 'description' => "تحديث حالة الطلب (تنفيذ، اكتمال، رفض).", 'icon_name' => "CheckCircle", 'available_in_base' => false, 'display_order' => 3], // Example: Not base
                    ['name' => "فلترة وبحث الطلبات", 'description' => "البحث عن طلبات محددة.", 'icon_name' => "CheckCircle", 'available_in_base' => false, 'display_order' => 4], // Example: Not base
                ]
            ],
             [
                'title' => "التقارير والإحصائيات",
                'icon_name' => "BarChart3",
                 'display_order' => 3,
                'features' => [
                    ['name' => "لوحة تحكم إحصائية", 'description' => "عرض ملخص سريع لعدد الطلبات والأصناف.", 'icon_name' => "CheckCircle", 'available_in_base' => true, 'display_order' => 1],
                    ['name' => "تقارير المبيعات الأساسية", 'description' => "عرض إجمالي المبيعات اليومية أو الأسبوعية.", 'icon_name' => "CheckCircle", 'available_in_base' => false, 'display_order' => 2], // Example: Not base
                    ['name' => "تقارير الأطباق الأكثر مبيعاً", 'description' => "معرفة الأطباق التي تحقق أعلى مبيعات.", 'icon_name' => "CheckCircle", 'available_in_base' => false, 'display_order' => 3], // Example: Not base
                ]
            ],
        ];
        // --- End Data Definition ---


        // --- Seeding Logic ---
        foreach ($featureGroups as $groupData) {
            // Create the category
            $category = FeatureCategory::create([
                'title' => $groupData['title'],
                'icon_name' => $groupData['icon_name'],
                'display_order' => $groupData['display_order'],
            ]);

            // Create features associated with this category
            if (!empty($groupData['features'])) {
                foreach ($groupData['features'] as $featureData) {
                    $category->features()->create($featureData); // Use relationship to auto-set category_id
                }
            }
        }
    }
}

