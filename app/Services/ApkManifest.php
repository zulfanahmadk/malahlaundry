<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;
use ZipArchive;

/** Read release identity from Android's binary XML, never trust browser form metadata. */
class ApkManifest
{
    public function read(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            $this->invalid();
        }
        try {
            $entry = $zip->statName('AndroidManifest.xml');
            if (! $entry || $entry['size'] > 1_048_576 || ! $zip->statName('classes.dex')) {
                $this->invalid();
            }
            $xml = $zip->getFromName('AndroidManifest.xml', 1_048_577);
            return $this->parse($xml ?: '');
        } finally {
            $zip->close();
        }
    }

    private function parse(string $xml): array
    {
        $length = strlen($xml);
        if ($length < 8 || $this->u16($xml, 0) !== 3 || $this->u32($xml, 4) !== $length) {
            $this->invalid();
        }
        $strings = [];
        for ($offset = 8; $offset + 8 <= $length;) {
            $type = $this->u16($xml, $offset);
            $header = $this->u16($xml, $offset + 2);
            $size = $this->u32($xml, $offset + 4);
            if ($size < $header || $header < 8 || $offset + $size > $length) {
                $this->invalid();
            }
            if ($type === 1) {
                if ($header < 28) {
                    $this->invalid();
                }
                $count = $this->u32($xml, $offset + 8);
                $utf8 = ($this->u32($xml, $offset + 16) & 0x100) !== 0;
                $start = $this->u32($xml, $offset + 20);
                if ($count > 20000 || $header + $count * 4 > $size) {
                    $this->invalid();
                }
                for ($i = 0; $i < $count; $i++) {
                    $position = $offset + $start + $this->u32($xml, $offset + $header + $i * 4);
                    if ($position < $offset + $header || $position >= $offset + $size) {
                        $this->invalid();
                    }
                    if ($utf8) {
                        $this->stringLength($xml, $position, true);
                        $bytes = $this->stringLength($xml, $position, true);
                    } else {
                        $bytes = $this->stringLength($xml, $position, false) * 2;
                    }
                    if ($position + $bytes > $offset + $size) {
                        $this->invalid();
                    }
                    $value = substr($xml, $position, $bytes);
                    $strings[$i] = $utf8 ? $value : mb_convert_encoding($value, 'UTF-8', 'UTF-16LE');
                }
            }
            if ($type === 0x102 && $size >= 36 && ($strings[$this->u32($xml, $offset + 20)] ?? '') === 'manifest') {
                $first = $offset + 16 + $this->u16($xml, $offset + 24);
                $attributeSize = $this->u16($xml, $offset + 26);
                $count = $this->u16($xml, $offset + 28);
                if ($attributeSize < 20 || $first < $offset + 36 || $first + $attributeSize * $count > $offset + $size) {
                    $this->invalid();
                }
                $values = [];
                for ($i = 0; $i < $count; $i++) {
                    $at = $first + $i * $attributeSize;
                    $name = $strings[$this->u32($xml, $at + 4)] ?? '';
                    $data = $this->u32($xml, $at + 16);
                    $dataType = ord($xml[$at + 15]);
                    $values[$name] = $dataType === 3 ? ($strings[$data] ?? '') : $data;
                }
                if (! in_array($values['package'] ?? null, ['com.malahlaundry.app', 'com.malahlaundry.app.qa'], true)
                    || ! is_string($values['versionName'] ?? null) || $values['versionName'] === '' || strlen($values['versionName']) > 100
                    || ! is_int($values['versionCode'] ?? null) || $values['versionCode'] < 1 || $values['versionCode'] > 2_100_000_000
                    || ($values['versionCodeMajor'] ?? 0) !== 0) {
                    $this->invalid();
                }
                return ['package_name' => $values['package'], 'version_code' => $values['versionCode'], 'version_name' => $values['versionName']];
            }
            $offset += $size;
        }
        $this->invalid();
    }

    private function stringLength(string $xml, int &$at, bool $utf8): int
    {
        if ($utf8) {
            if ($at >= strlen($xml)) {
                $this->invalid();
            }
            $value = ord($xml[$at++]);
            if (($value & 0x80) !== 0) {
                if ($at >= strlen($xml)) {
                    $this->invalid();
                }
                $value = (($value & 0x7f) << 8) | ord($xml[$at++]);
            }
            return $value;
        }
        $value = $this->u16($xml, $at);
        $at += 2;
        if (($value & 0x8000) !== 0) {
            $value = (($value & 0x7fff) << 16) | $this->u16($xml, $at);
            $at += 2;
        }
        return $value;
    }

    private function u16(string $data, int $at): int
    {
        if ($at < 0 || $at + 2 > strlen($data)) {
            $this->invalid();
        }
        return unpack('v', substr($data, $at, 2))[1];
    }

    private function u32(string $data, int $at): int
    {
        if ($at < 0 || $at + 4 > strlen($data)) {
            $this->invalid();
        }
        return unpack('V', substr($data, $at, 4))[1];
    }

    private function invalid(): never
    {
        throw ValidationException::withMessages(['apk' => 'APK tidak valid. Gunakan APK Malah Laundry dengan package dan versi build yang sesuai.']);
    }
}
