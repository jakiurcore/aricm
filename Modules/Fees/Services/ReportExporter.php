<?php

namespace Modules\Fees\Services;

use Modules\Core\Services\PdfService;
use Modules\Core\Support\Branding;
use Modules\Fees\Support\ReportTable;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class ReportExporter
{
    public function __construct(protected PdfService $pdf) {}

    public function pdf(ReportTable $table): string
    {
        $html = view('fees::pdf.report', ['table' => $table, 'brand' => Branding::forPdf()])->render();

        return $this->pdf->render($html, ['orientation' => count($table->columns) > 6 ? 'L' : 'P', 'showImageErrors' => false]);
    }

    /**
     * An .xlsx with real numbers (money in taka, not text) so the accountant can add it up.
     */
    public function xlsx(ReportTable $table): string
    {
        $path = tempnam(sys_get_temp_dir(), 'report');
        $writer = new Writer;
        $writer->openToFile($path);

        $writer->addRow(Row::fromValues([$table->title]));
        $writer->addRow(Row::fromValues(array_column($table->columns, 'label')));

        foreach ($table->rows as $row) {
            $writer->addRow(Row::fromValues(array_map(fn (array $column): mixed => $this->cell($column['type'], $row[$column['key']] ?? null), $table->columns)));
        }

        if ($table->totals !== []) {
            $writer->addRow(Row::fromValues(array_map(fn (array $column, int $index): mixed => isset($table->totals[$column['key']]) ? $this->cell($column['type'], $table->totals[$column['key']]) : ($index === 0 ? __('fees::reports.total') : null), $table->columns, array_keys($table->columns))));
        }

        $writer->close();
        $bytes = (string) file_get_contents($path);
        unlink($path);

        return $bytes;
    }

    protected function cell(string $type, mixed $value): mixed
    {
        return match ($type) {
            'money' => ((int) $value) / 100,
            'int' => $value === null ? null : (int) $value,
            default => $value,
        };
    }
}
