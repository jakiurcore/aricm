<?php

namespace Modules\Gallery\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Gallery\Models\Album;

/**
 * @extends Factory<Album>
 */
class AlbumFactory extends Factory
{
    protected $model = Album::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['title_bn' => 'অ্যালবাম '.fake()->unique()->numberBetween(1, 9999), 'title_en' => null, 'is_published' => true, 'sort_order' => 0, 'album_date' => '2026-03-26'];
    }
}
