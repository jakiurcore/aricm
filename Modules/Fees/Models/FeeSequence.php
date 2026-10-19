<?php

namespace Modules\Fees\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'year', 'last'])]
class FeeSequence extends Model
{
    public $timestamps = false;
}
