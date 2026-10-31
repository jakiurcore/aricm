<?php

namespace Modules\Website\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Website\Models\WebsiteItem;

/**
 * @extends Factory<WebsiteItem>
 */
class WebsiteItemFactory extends Factory
{
    protected $model = WebsiteItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'collection' => 'why',
            'title_bn' => 'আইটেম '.fake()->unique()->numberBetween(1, 9999),
            'title_en' => null,
            'body_bn' => null,
            'body_en' => null,
            'icon' => null,
            'meta' => null,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
