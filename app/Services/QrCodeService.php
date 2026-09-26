<?php

namespace App\Services;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class QrCodeService
{
    /**
     * Generate data URI (SVG base64) untuk ditampilkan pada tag <img>.
     */
    public static function generateDataUri(string $data): string
    {
        try {
            $options = new QROptions();
            $options->outputType = QRCode::OUTPUT_MARKUP_SVG;
            $options->svgUseFill = true;
            $options->scale = 6;
            $options->outputBase64 = true;

            return (new QRCode($options))->render($data);
        } catch (\Throwable $e) {
            // Fallback placeholder
            $encoded = base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="160" height="160" viewBox="0 0 160 160"><rect width="160" height="160" fill="#f8fafc"/><text x="80" y="80" text-anchor="middle" fill="#0284c7" font-size="12">QR CODE</text></svg>');
            return 'data:image/svg+xml;base64,' . $encoded;
        }
    }
}
