<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path')->unique();
            $table->string('to_path');
            $table->unsignedInteger('hits')->default(0);
            $table->timestamps();

            $table->index('to_path');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirects');
    }
};
