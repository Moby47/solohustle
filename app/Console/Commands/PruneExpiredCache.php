<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Deletes expired rows from the database cache table. The page cache throws old copies away by changing a version
 * number, so without this the table would keep growing.
 */
class PruneExpiredCache extends Command
{
    protected $signature = 'cache:prune-expired';

    protected $description = 'Delete expired entries from the database cache table';

    public function handle(): int
    {
        if (config('cache.default') !== 'database') {
            $this->info('Cache is not stored in the database; nothing to prune.');

            return self::SUCCESS;
        }

        $table = config('cache.stores.database.table', 'cache');
        $deleted = DB::table($table)->where('expiration', '<', now()->getTimestamp())->delete();

        $this->info("Deleted {$deleted} expired cache entries.");

        return self::SUCCESS;
    }
}
