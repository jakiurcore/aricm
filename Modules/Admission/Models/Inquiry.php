<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Admission\Database\Factories\InquiryFactory;

#[UseFactory(InquiryFactory::class)]
#[Fillable(['student_name', 'guardian_name', 'phone', 'desired_class', 'message', 'status', 'admin_note', 'ip'])]
class Inquiry extends Model
{
    /** @use HasFactory<InquiryFactory> */
    use HasFactory;

    public const array STATUSES = ['new', 'contacted', 'admitted', 'rejected'];

    protected $table = 'admission_inquiries';
}
