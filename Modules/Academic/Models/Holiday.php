<?php

namespace Modules\Academic\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['date', 'title_bn', 'title_en'])]
class Holiday extends Model
{
    protected $table = 'school_calendar_holidays';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['date' => 'date:Y-m-d'];
    }

    public function title(): string
    {
        return localized($this, 'title') ?? $this->title_bn;
    }
}
