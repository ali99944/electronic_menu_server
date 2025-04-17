<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ChangelogVersion;
use App\Models\ChangelogPoint;
use Illuminate\Support\Facades\DB; // Keep DB Facade

class ChangelogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // --- PostgreSQL: Disable Triggers for Truncate ---
        $pointTable = (new ChangelogPoint)->getTable();
        $versionTable = (new ChangelogVersion)->getTable();

        DB::statement("ALTER TABLE \"{$pointTable}\" DISABLE TRIGGER ALL;");
        DB::statement("ALTER TABLE \"{$versionTable}\" DISABLE TRIGGER ALL;");

        // Truncate tables - Use RESTART IDENTITY if needed
        ChangelogPoint::truncate();
        ChangelogVersion::truncate();

        // Re-enable triggers
        DB::statement("ALTER TABLE \"{$pointTable}\" ENABLE TRIGGER ALL;");
        DB::statement("ALTER TABLE \"{$versionTable}\" ENABLE TRIGGER ALL;");
        // --- End PostgreSQL specific block ---


        // --- Data Definition (Keep as is) ---
        $changelogData = [
            // ... your changelog data ...
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


        // --- Seeding Logic (Keep as is) ---
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
