<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** MySQL FULLTEXT indexes for site search (Scout "database" driver). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->fullText(['excerpt', 'body'], 'posts_search_fulltext');
        });

        Schema::table('job_listings', function (Blueprint $table) {
            $table->fullText(['summary', 'requirements'], 'job_listings_search_fulltext');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropFullText('posts_search_fulltext');
        });

        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropFullText('job_listings_search_fulltext');
        });
    }
};
