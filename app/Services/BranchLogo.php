<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class BranchLogo
{
    public function normalize(?string $encoded): ?string
    {
        if (! $encoded) {
            return null;
        }
        $bytes = base64_decode($encoded, true);
        $info = is_string($bytes) && strlen($bytes) <= 1048576 ? @getimagesizefromstring($bytes) : false;
        if (! $info || ! in_array($info['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)
            || $info[0] > 4096 || $info[1] > 4096) {
            throw ValidationException::withMessages(['logo_base64' => 'Logo harus JPG, PNG, atau WebP, maksimal 1 MB dan 4096 × 4096 piksel.']);
        }
        $source = imagecreatefromstring($bytes);
        $scale = min(1, 384 / max($info[0], $info[1]));
        $width = max(1, (int) round($info[0] * $scale));
        $height = max(1, (int) round($info[1] * $scale));
        $image = imagecreatetruecolor($width, $height);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagecopyresampled($image, $source, 0, 0, 0, 0, $width, $height, $info[0], $info[1]);
        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($source);
        imagedestroy($image);
        return base64_encode($png);
    }
}
