<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

/** Turns uploads into WebP files on the media disk, in the sizes the site needs. */
final class ImageProcessor
{
    private const QUALITY = 80;

    /** Biggest photo we'll decode (a 24-megapixel camera photo is fine). */
    private const MAX_PIXELS = 30_000_000;

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
     * Fit inside $maxWidth × $maxHeight without cropping (for logos).
     */
    public function storeContained(UploadedFile $file, string $directory, int $maxWidth, int $maxHeight): string
    {
        $path = $this->newBasePath($directory).'.webp';

        $this->put($path, $this->read($file)->scaleDown($maxWidth, $maxHeight));

        return $path;
    }

    /**
     * Copy a single stored image to a new name, so two records never share (and later delete) the same file.
     */
    public function copy(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $disk = Storage::disk(config('media.disk'));

        if (! $disk->exists($path)) {
            return null;
        }

        $newPath = $this->newBasePath(dirname($path)).'.webp';
        $disk->copy($path, $newPath);

        return $newPath;
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
        $size = @getimagesize($file->getRealPath());

        if ($size === false) {
            throw ValidationException::withMessages(['image' => 'That file doesn\'t look like an image. Please upload a JPG, PNG or WebP.']);
        }

        // Decoding needs about 4 bytes per pixel; refuse giant photos politely instead of running out of memory.
        if ($size[0] * $size[1] > self::MAX_PIXELS) {
            throw ValidationException::withMessages(['image' => 'That image is very large. Please use one under '.(self::MAX_PIXELS / 1_000_000).' megapixels (for example 6000 × 4000).']);
        }

        @ini_set('memory_limit', '512M');

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
