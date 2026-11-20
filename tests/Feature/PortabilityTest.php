<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

/**
 * Boot the app in a fresh PHP process with the given environment variables and return the resolved values.
 *
 * @param  array<string, string>  $env
 * @return array{public_path: string, media_root: string, db_url: ?string}
 */
function bootWithEnv(array $env): array
{
    $code = <<<'PHP'
        require 'vendor/autoload.php';
        $app = require 'bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        echo json_encode([
            'public_path' => public_path(),
            'media_root' => config('filesystems.disks.media_local.root'),
            'db_url' => config('database.connections.mysql.url'),
        ]);
        PHP;

    $result = Process::path(base_path())->env($env)->run([PHP_BINARY, '-r', $code])->throw();

    return json_decode($result->output(), true);
}

test('uploads go to public/uploads by default', function () {
    $disk = Storage::disk(config('media.disk'));

    $disk->put('portability-test.txt', 'ok');

    expect(config('media.disk'))->toBe('media_local')
        ->and(file_exists(public_path('uploads/portability-test.txt')))->toBeTrue()
        ->and($disk->url('portability-test.txt'))->toBe(config('app.url').'/uploads/portability-test.txt');

    $disk->delete('portability-test.txt');
});

test('APP_PUBLIC_PATH moves the web root and the uploads folder', function () {
    $webRoot = sys_get_temp_dir().DIRECTORY_SEPARATOR.'public_html';

    $resolved = bootWithEnv(['APP_PUBLIC_PATH' => $webRoot]);

    expect($resolved['public_path'])->toBe($webRoot)
        ->and($resolved['media_root'])->toBe($webRoot.'/uploads');
});

test('the database falls back to JAWSDB_URL when DB_URL is empty', function () {
    $resolved = bootWithEnv(['DB_URL' => '', 'JAWSDB_URL' => 'mysql://user:secret@jawsdb.example:3306/app']);

    expect($resolved['db_url'])->toBe('mysql://user:secret@jawsdb.example:3306/app');
});
