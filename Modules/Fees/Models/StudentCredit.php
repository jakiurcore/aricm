<?php

namespace Modules\Fees\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Modules\Fees\Casts\MoneyCast;

#[Fillable(['student_id', 'balance'])]
class StudentCredit extends Model
{
    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['balance' => MoneyCast::class];
    }
}
