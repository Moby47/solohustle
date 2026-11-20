<?php

declare(strict_types=1);

return [

    // Which filesystem disk holds uploads: media_local (public/uploads) or media_s3 (R2 bucket).
    'disk' => env('MEDIA_DISK', 'media_local'),

];
