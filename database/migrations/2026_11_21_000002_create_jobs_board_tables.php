<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Named "job_listings" because Laravel's queue already owns the "jobs" table. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('intro')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->unsignedInteger('sort')->default(0)->index();
            $table->foreignId('related_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_worldwide')->default(false);
            $table->unsignedInteger('sort')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('job_listings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('company_name');
            $table->string('company_url', 500)->nullable();
            $table->string('company_logo')->nullable();
            $table->foreignId('job_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('region_id')->constrained()->restrictOnDelete();
            $table->char('country_code', 2)->nullable();
            $table->string('location_note')->nullable();
            $table->string('work_mode', 20);
            $table->string('employment_type', 20);
            $table->string('experience_level', 20);
            $table->text('summary');
            $table->text('requirements')->nullable();
            $table->string('salary_text')->nullable();
            $table->string('apply_url', 500);
            $table->string('source_name')->nullable();
            $table->string('source_url', 500)->nullable();
            $table->date('posted_at')->index();
            $table->timestamp('last_verified_at')->nullable()->index();
            $table->date('closes_at')->nullable();
            $table->string('status', 20)->default('open');
            $table->timestamp('closed_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->string('seo_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->timestamps();

            $table->index(['status', 'posted_at']);
            $table->index(['status', 'job_category_id', 'posted_at']);
            $table->index(['status', 'region_id']);
            $table->index(['status', 'work_mode']);
            $table->index(['status', 'closes_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_listings');
        Schema::dropIfExists('regions');
        Schema::dropIfExists('job_categories');
    }
};
