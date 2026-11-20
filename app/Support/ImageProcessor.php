<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

/** Turns uploads into WebP files on the media disk, in the sizes the site needs. */
final class ImageProcessor
{
    private const QUALITY = 80;

    /**
     * Crop to exactly $width × $height, plus narrower copies saved as "name-{width}.webp" for srcset.
     *
     * @param  list<int>  $smallerWidths
     * @return string The path of the full-size image.
     */
    public function storeCropped(UploadedFile $file, string $directory, int $width, int $height, array $smallerWidths = []): string
    {
        $image = $this->read($file);
        $basePath = $this->newBasePath($directory);

        $this->put("{$basePath}.webp", (clone $image)->cover($width, $height));

        foreach ($smallerWidths as $smallerWidth) {
            $smallerHeight = (int) round($height * $smallerWidth / $width);
            $this->put("{$basePath}-{$smallerWidth}.webp", (clone $image)->cover($smallerWidth, $smallerHeight));
        }

        return "{$basePath}.webp";
    }

    /**
     * Keep the shape, but never wider than $maxWidth.
     */
    public function storeScaled(UploadedFile $file, string $directory, int $maxWidth): string
    {
        $path = $this->newBasePath($directory).'.webp';

        $this->put($path, $this->read($file)->scaleDown(width: $maxWidth));

        return $path;
    }

    /**
     * Delete an image and any narrower copies made from it.
     */
    public function delete(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        $disk = Storage::disk(config('media.disk'));
        $stem = Str::beforeLast($path, '.webp');

        $copies = array_filter(
            $disk->files(dirname($path)),
            fn (string $file): bool => preg_match('/^'.preg_quote($stem, '/').'-\d+\.webp$/', $file) === 1,
        );

        $disk->delete([$path, ...$copies]);
    }

    private function read(UploadedFile $file): ImageInterface
    {
        return ImageManager::gd()->read($file->getRealPath());
    }

    private function newBasePath(string $directory): string
    {
        return trim($directory, '/').'/'.Str::lower(Str::random(32));
    }

    private function put(string $path, ImageInterface $image): void
    {
        Storage::disk(config('media.disk'))->put($path, (string) $image->toWebp(self::QUALITY), 'public');
    }
}
