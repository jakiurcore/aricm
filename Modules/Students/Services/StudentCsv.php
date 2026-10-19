<?php

namespace Modules\Students\Services;

/**
 * The CSV layout shared by import, export and the downloadable template.
 */
final class StudentCsv
{
    /**
     * @var list<string>
     */
    public const array COLUMNS = [
        'name_bn', 'name_en', 'class', 'section', 'roll', 'father_name', 'mother_name',
        'guardian_name', 'guardian_phone', 'guardian_relation', 'date_of_birth', 'gender',
        'blood_group', 'religion', 'present_address', 'permanent_address', 'admission_date', 'residency_type',
    ];

    public const string BOM = "\xEF\xBB\xBF";
}
