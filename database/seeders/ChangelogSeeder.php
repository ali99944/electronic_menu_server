<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ChangelogVersion;
use App\Models\ChangelogPoint;
use Illuminate\Support\Facades\DB;

class ChangelogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        ChangelogPoint::truncate();
        ChangelogVersion::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // --- Data Definition (Matches frontend example) ---
        $changelogData = [
            [
                'version' => '1.1',
                'release_date' => 'قادم قريباً',
                'points' => [
                    ['type' => 'new', 'description' => 'إضافة صفحة سجل التغييرات.'],
                    ['type' => 'new', 'description' => 'إضافة صفحة الإمكانيات لعرض ميزات النظام.'],
                    ['type' => 'new', 'description' => 'إضافة صفحة الدعم الفني.'],
                    ['type' => 'new', 'description' => 'إضافة صفحة تغيير كلمة المرور.'],
                    ['type' => 'improvement', 'description' => 'تحسين تصميم واجهات لوحة التحكم.'],
                    ['type' => 'improvement', 'description' => 'إضافة إمكانية البحث في الطلبات (مستقبلاً).'],
                    ['type' => 'improvement', 'description' => 'تحسينات عامة في الأداء والاستقرار.'],
                ],
            ],
            [
                'version' => '1.0',
                'release_date' => 'الإصدار الأولي',
                'points' => [
                    ['type' => 'new', 'description' => 'لوحة تحكم أساسية لعرض الإحصائيات.'],
                    ['type' => 'new', 'description' => 'إدارة الأطباق مع دعم الأنواع والأسعار المختلفة.'],
                    ['type' => 'new', 'description' => 'إدارة التصنيفات.'],
                    ['type' => 'new', 'description' => 'نظام تسجيل الدخول للوحة التحكم.'],
                    ['type' => 'new', 'description' => 'عرض الطلبات الحديثة (إذا كانت الطلبات مفعلة).'],
                    ['type' => 'new', 'description' => 'تكامل أساسي مع Pusher لاستقبال الطلبات.'],
                ],
            ],
        ];
        // --- End Data Definition ---


        // --- Seeding Logic ---
        foreach ($changelogData as $versionData) {
            // Create the version
            $version = ChangelogVersion::create([
                'version' => $versionData['version'],
                'release_date' => $versionData['release_date'],
            ]);

            // Create points associated with this version
            if (!empty($versionData['points'])) {
                foreach ($versionData['points'] as $pointData) {
                    $version->points()->create($pointData); // Use relationship
                }
            }
        }
    }
}
