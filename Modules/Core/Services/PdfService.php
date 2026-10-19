<?php

namespace Modules\Core\Services;

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

/**
 * mPDF wrapper with the embedded Bangla font (Arabic uses mPDF's bundled XB Riyaz). OpenType layout (useOTL 0xFF) is switched on for every
 * font so Bangla conjuncts (যুক্তাক্ষর) shape correctly; dompdf cannot do this, which is why it is not used.
 *
 * mPDF understands a CSS 2-ish subset: build layouts with tables, `mm` units and absolute positioning, not
 * flexbox or grid.
 */
class PdfService
{
    public const string DEFAULT_FONT = 'notosansbengali';

    public const string HEADING_FONT = 'notoserifbengali';

    public const string ARABIC_FONT = 'xbriyaz';

    /**
     * @param  array<string, mixed>  $options  Any mPDF constructor option (format, orientation, margin_top…).
     */
    public function make(array $options = []): Mpdf
    {
        $fontDirs = (new ConfigVariables)->getDefaults()['fontDir'];
        $fontData = (new FontVariables)->getDefaults()['fontdata'];

        $directory = storage_path('app/mpdf');
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        return new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 0, 'margin_right' => 0, 'margin_top' => 0, 'margin_bottom' => 0, 'margin_header' => 0, 'margin_footer' => 0,
            'tempDir' => $directory,
            'fontDir' => [...$fontDirs, base_path('Modules/Core/resources/fonts')],
            'fontdata' => $fontData + [
                self::DEFAULT_FONT => ['R' => 'HindSiliguri-Regular.ttf', 'B' => 'HindSiliguri-Bold.ttf', 'useOTL' => 0xFF, 'useKashida' => 75],
                self::HEADING_FONT => ['R' => 'HindSiliguri-SemiBold.ttf', 'B' => 'HindSiliguri-Bold.ttf', 'useOTL' => 0xFF, 'useKashida' => 75],
            ],
            'default_font' => self::DEFAULT_FONT,
            'autoScriptToLang' => false,
            'autoLangToFont' => false,
            'useSubstitutions' => true,
            ...$options,
        ]);
    }

    /**
     * Render one or more HTML pages (separated by <pagebreak />) to PDF bytes.
     *
     * @param  array<string, mixed>  $options
     */
    public function render(string $html, array $options = []): string
    {
        $mpdf = $this->make($options);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', Destination::STRING_RETURN);
    }
}
