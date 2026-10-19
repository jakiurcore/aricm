<?php

namespace Modules\Results\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Results\Models\GradingScale;

class GradingScaleSeeder extends Seeder
{
    /**
     * The Bangladesh default scale, written once; edits made in the admin are kept.
     */
    public function run(): void
    {
        if (GradingScale::query()->exists()) {
            return;
        }

        $scale = GradingScale::create(['name' => 'বাংলাদেশ ডিফল্ট (৫.০০)', 'is_default' => true]);

        foreach ([[80, 100, 'A+', 5.00], [70, 79.99, 'A', 4.00], [60, 69.99, 'A-', 3.50], [50, 59.99, 'B', 3.00], [40, 49.99, 'C', 2.00], [33, 39.99, 'D', 1.00], [0, 32.99, 'F', 0.00]] as [$min, $max, $grade, $point]) {
            $scale->items()->create(['min_percent' => $min, 'max_percent' => $max, 'grade' => $grade, 'grade_point' => $point]);
        }
    }
}
