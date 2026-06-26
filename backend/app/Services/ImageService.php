<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageService
{
    public function upload(UploadedFile $file, string $folder): string
    {
        $manager = new ImageManager(new Driver());
        $image = $manager->read($file)->toWebp(90);
        $name = Str::uuid() . '.webp';

        Storage::disk('public')->put("{$folder}/{$name}", $image);

        return "storage/{$folder}/{$name}";
    }

    public function uploadFromUrl(string $url, string $folder): ?string
    {
        try {
            $response = Http::timeout(15)->get($url);

            if (!$response->successful()) {
                return null;
            }

            $manager = new ImageManager(new Driver());
            $image = $manager->read($response->body())->toWebp(90);
            $name = Str::uuid() . '.webp';

            Storage::disk('public')->put("{$folder}/{$name}", $image);

            return "storage/{$folder}/{$name}";
        } catch (\Throwable) {
            return null;
        }
    }
}
