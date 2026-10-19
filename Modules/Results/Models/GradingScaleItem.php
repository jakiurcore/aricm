<?php

namespace Modules\Results\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['grading_scale_id', 'min_percent', 'max_percent', 'grade', 'grade_point'])]
class GradingScaleItem extends Model
{
    public $timestamps = false;
}
