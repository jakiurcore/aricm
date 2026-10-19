<?php

namespace Modules\Fees\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Modules\Fees\Casts\MoneyCast;

#[Fillable(['exam_id', 'class_id', 'amount', 'due_date'])]
class ExamFeeAmount extends Model
{
    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['amount' => MoneyCast::class, 'due_date' => 'date'];
    }
}
