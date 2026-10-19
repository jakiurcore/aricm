<?php

namespace Modules\Core\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * QR codes as SVG data URIs: vector (prints sharp on cards), and no GD/Imagick needed to make them.
 */
class QrCodeService
{
    public function svgDataUri(string $content, int $size = 200): string
    {
        $svg = QrCode::format('svg')->size($size)->margin(0)->errorCorrection('M')->generate($content);

        return 'data:image/svg+xml;base64,'.base64_encode((string) $svg);
    }

    /**
     * The QR for /verify/{type}/{hash}.
     */
    public function forVerification(string $type, string $hash, int $size = 200): string
    {
        return $this->svgDataUri(app(VerificationRegistry::class)->url($type, $hash), $size);
    }
}
