<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->string('headline')->nullable()->after('role');
            $table->text('bio')->nullable()->after('headline');
            $table->string('photo')->nullable()->after('bio');
            $table->string('website_url')->nullable()->after('photo');
            $table->string('linkedin_url')->nullable()->after('website_url');
            $table->string('x_url')->nullable()->after('linkedin_url');
            $table->timestamp('suspended_at')->nullable()->after('email_verified_at')->index();
        });

        DB::table('users')->whereNull('slug')->orderBy('id')->each(function (object $user) {
            DB::table('users')->where('id', $user->id)->update(['slug' => Str::slug($user->name).'-'.$user->id]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropIndex(['suspended_at']);
            $table->dropColumn(['slug', 'headline', 'bio', 'photo', 'website_url', 'linkedin_url', 'x_url', 'suspended_at']);
        });
    }
};
