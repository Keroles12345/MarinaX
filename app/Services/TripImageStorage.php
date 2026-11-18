<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class TripImageStorage
{
    public function store(UploadedFile $file, int $tenantId): string
    {
        $contents = file_get_contents($file->getRealPath());
        if ($contents === false) {
            throw new RuntimeException('تعذر قراءة الصورة المرفوعة.');
        }

        $size = @getimagesizefromstring($contents);
        if ($size === false || $size[0] * $size[1] > 20000000) {
            throw new RuntimeException('أبعاد الصورة غير صالحة أو أكبر من الحد المسموح.');
        }

        $image = @imagecreatefromstring($contents);
        if ($image === false) {
            throw new RuntimeException('تعذر معالجة الصورة المرفوعة.');
        }

        try {
            $width = imagesx($image);
            $height = imagesy($image);
            $scale = min(1, 1920 / max($width, $height));
            $targetWidth = max(1, (int) round($width * $scale));
            $targetHeight = max(1, (int) round($height * $scale));
            $resized = imagecreatetruecolor($targetWidth, $targetHeight);

            if ($resized === false) {
                throw new RuntimeException('تعذر تجهيز الصورة للحفظ.');
            }

            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

            ob_start();
            $encoded = imagewebp($resized, null, 80);
            $webp = ob_get_clean();
            imagedestroy($resized);

            if (! $encoded || ! is_string($webp) || $webp === '') {
                throw new RuntimeException('تعذر تحويل الصورة إلى WebP.');
            }
        } finally {
            imagedestroy($image);
        }

        $path = "trips/{$tenantId}/".bin2hex(random_bytes(16)).'.webp';
        if (! Storage::disk('public')->put($path, $webp)) {
            throw new RuntimeException('تعذر حفظ صورة الرحلة في التخزين المحلي.');
        }

        return $path;
    }
}
