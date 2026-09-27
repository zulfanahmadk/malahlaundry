<?php

namespace App\Services;

use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class QrCodeService
{
    /**
     * Generate data URI (SVG base64) untuk ditampilkan pada tag <img>.
     */
    public static function generateDataUri(string $data): string
    {
        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'svgUseFillAttributes' => true,
            'outputBase64' => true,
        ]);

        return (new QRCode($options))->render($data);
    }
}
