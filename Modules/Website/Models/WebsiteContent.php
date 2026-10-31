<?php

namespace Modules\Website\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Modules\Website\Models\Concerns\FlushesSiteContent;

/**
 * One editable scalar text or image, keyed by its ContentSchema key.
 */
#[Fillable(['key', 'value_bn', 'value_en', 'image'])]
class WebsiteContent extends Model
{
    use FlushesSiteContent;
}
