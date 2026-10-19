<?php

namespace Modules\Fees\Support;

/**
 * A report as data: typed columns, raw rows and totals. The UI, the PDF and the Excel export all render the same table.
 * Column types: text, int, money (rows hold whole poisha), date (rows hold Y-m-d).
 */
final readonly class ReportTable
{
    /**
     * @param  list<array{key: string, label: string, type: string}>  $columns
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, mixed>  $totals  Column key => total, shown in a footer row.
     */
    public function __construct(
        public string $title,
        public array $columns,
        public array $rows,
        public array $totals = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['title' => $this->title, 'columns' => $this->columns, 'rows' => $this->rows, 'totals' => $this->totals];
    }

    /**
     * A cell as text for print: money with the taka sign, dates in the locale, numbers localised.
     */
    public function display(string $type, mixed $value): string
    {
        return match ($type) {
            'money' => Money::poisha((int) $value)->format(),
            'int' => bn_digits((string) $value),
            'date' => $value ? bn_date($value) : '',
            default => (string) $value,
        };
    }
}
