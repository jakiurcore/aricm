<?php

namespace Modules\Students\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Services\PdfService;
use Modules\Core\Services\QrCodeService;
use Modules\Core\Support\Branding;
use Modules\Core\Support\SignedHash;
use Modules\Students\Models\Student;

/**
 * CR80 ID cards (54 × 85.6 mm), nine to an A4 sheet with crop marks: one page of fronts, then one page of backs
 * with the columns mirrored so a long-edge duplex print lines the two sides up.
 */
class IdCardPdf
{
    public const int COLUMNS = 3;

    public const int ROWS = 3;

    public const float CARD_WIDTH = 54.0;

    public const float CARD_HEIGHT = 85.6;

    public const float GAP = 4.0;

    public function __construct(protected PdfService $pdf, protected QrCodeService $qr) {}

    /**
     * @param  Collection<int, Student>  $students
     */
    public function render(Collection $students): string
    {
        $students->each->loadMissing(['schoolClass', 'section', 'session']);

        $perSheet = self::COLUMNS * self::ROWS;
        $sheets = $students->chunk($perSheet)->map(fn (Collection $chunk): array => [
            'front' => $this->cards($chunk, mirror: false),
            'back' => $this->cards($chunk, mirror: true),
        ]);

        $html = view('students::pdf.id-cards', [
            'sheets' => $sheets,
            'brand' => Branding::forPdf(),
            'marks' => $this->cropMarks(),
        ])->render();

        return $this->pdf->render($html, ['showImageErrors' => false]);
    }

    /**
     * @param  Collection<int, Student>  $chunk
     * @return list<array<string, mixed>>
     */
    protected function cards(Collection $chunk, bool $mirror): array
    {
        $cards = [];

        foreach ($chunk->values() as $index => $student) {
            $column = $index % self::COLUMNS;
            $row = intdiv($index, self::COLUMNS);
            $column = $mirror ? self::COLUMNS - 1 - $column : $column;

            $cards[] = [
                'x' => $this->originX() + $column * (self::CARD_WIDTH + self::GAP),
                'y' => $this->originY() + $row * (self::CARD_HEIGHT + self::GAP),
                'student' => $student,
                'photo' => $student->photo && Storage::disk('public')->exists($student->photo) ? Storage::disk('public')->path($student->photo) : null,
                'qr' => $this->qr->forVerification('student-id', SignedHash::make('student-id', $student->id), 120),
                'validUntil' => bn_date($student->session?->end_date ?? now()->endOfYear(), true),
            ];
        }

        return $cards;
    }

    protected function originX(): float
    {
        return (210 - (self::COLUMNS * self::CARD_WIDTH + (self::COLUMNS - 1) * self::GAP)) / 2;
    }

    protected function originY(): float
    {
        return (297 - (self::ROWS * self::CARD_HEIGHT + (self::ROWS - 1) * self::GAP)) / 2;
    }

    /**
     * Short L-shaped marks just outside the corners of every card position.
     *
     * @return list<array{x: float, y: float, w: float, h: float}>
     */
    protected function cropMarks(): array
    {
        $marks = [];
        $length = 2.5;
        $offset = 1.0;

        for ($column = 0; $column < self::COLUMNS; $column++) {
            for ($row = 0; $row < self::ROWS; $row++) {
                $left = $this->originX() + $column * (self::CARD_WIDTH + self::GAP);
                $top = $this->originY() + $row * (self::CARD_HEIGHT + self::GAP);

                foreach ([[$left, $top], [$left + self::CARD_WIDTH, $top], [$left, $top + self::CARD_HEIGHT], [$left + self::CARD_WIDTH, $top + self::CARD_HEIGHT]] as $index => [$cx, $cy]) {
                    $isRight = $index % 2 === 1;
                    $isBottom = $index >= 2;

                    $marks[] = ['x' => $isRight ? $cx + $offset : $cx - $offset - $length, 'y' => $cy, 'w' => $length, 'h' => 0.15];
                    $marks[] = ['x' => $cx, 'y' => $isBottom ? $cy + $offset : $cy - $offset - $length, 'w' => 0.15, 'h' => $length];
                }
            }
        }

        return $marks;
    }
}
