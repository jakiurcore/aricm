<?php

namespace Modules\Website\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Modules\Website\Models\Concerns\FlushesSiteContent;

/**
 * Overrides a home section's visibility and position; `key` is the section's view name.
 */
#[Fillable(['key', 'is_enabled', 'sort_order'])]
class WebsiteSection extends Model
{
    use FlushesSiteContent;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['is_enabled' => 'boolean', 'sort_order' => 'integer'];
    }
}
