<?php

namespace Modules\Notices\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Notices\Enums\NoticeCategory;
use Modules\Notices\Models\Notice;

/**
 * @extends Factory<Notice>
 */
class NoticeFactory extends Factory
{
    protected $model = Notice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title_bn' => 'নোটিশ '.fake()->unique()->numberBetween(1, 9999),
            'title_en' => null,
            'body_bn' => 'নোটিশের বিস্তারিত।',
            'body_en' => null,
            'category' => NoticeCategory::General,
            'is_pinned' => false,
            'show_in_ticker' => false,
            'is_published' => true,
            'publish_at' => null,
        ];
    }
}
