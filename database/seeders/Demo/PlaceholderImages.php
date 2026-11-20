<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use App\Models\Post;
use App\Support\ImageProcessor;
use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

/** Simple branded placeholder images, drawn locally (never downloaded), saved through the normal image pipeline. */
final class PlaceholderImages
{
    private const PALETTE = ['#0750A1', '#B99BD8', '#F5D522', '#053C79', '#E6EEF7', '#F3EEFA'];

    public function __construct(private readonly ImageProcessor $images) {}

    public function cover(int $seed): string
    {
        return $this->images->storeCropped(
            $this->upload($this->abstract(Post::COVER_WIDTH, Post::COVER_HEIGHT, $seed)),
            'covers', Post::COVER_WIDTH, Post::COVER_HEIGHT, Post::COVER_SMALLER_WIDTHS,
        );
    }

    public function avatar(int $seed): string
    {
        return $this->images->storeCropped($this->upload($this->abstract(400, 400, $seed)), 'authors', 400, 400);
    }

    public function logo(int $seed): string
    {
        $color = self::PALETTE[$seed % count(self::PALETTE)];
        $accent = self::PALETTE[($seed + 2) % count(self::PALETTE)];

        $image = ImageManager::gd()->create(200, 200)->fill('#ffffff');
        $image->drawRectangle(30, 30, fn ($r) => $r->size(140, 140)->background($color)->border($color));
        $image->drawCircle(100, 100, fn ($c) => $c->radius(38)->background($accent));

        return $this->images->storeContained($this->upload($image), 'logos', 200, 200);
    }

    private function abstract(int $width, int $height, int $seed): ImageInterface
    {
        $base = self::PALETTE[$seed % 2 === 0 ? 0 : 3];
        $image = ImageManager::gd()->create($width, $height)->fill($base);

        mt_srand($seed);

        foreach (range(1, 5) as $index) {
            $color = self::PALETTE[($seed + $index) % count(self::PALETTE)];
            $radius = mt_rand((int) ($height * 0.15), (int) ($height * 0.45));
            $image->drawCircle(mt_rand(0, $width), mt_rand(0, $height), fn ($circle) => $circle->radius($radius)->background($color));
        }

        mt_srand();

        return $image;
    }

    private function upload(ImageInterface $image): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'demo').'.png';
        file_put_contents($path, (string) $image->toPng());

        return new UploadedFile($path, 'placeholder.png', 'image/png', null, true);
    }
}
