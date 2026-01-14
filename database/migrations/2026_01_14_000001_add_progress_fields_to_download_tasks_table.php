<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('download_tasks', function (Blueprint $table) {
            $table->float('progress_percentage')->nullable()->after('error_message');
            $table->string('progress_eta')->nullable()->after('progress_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('download_tasks', function (Blueprint $table) {
            $table->dropColumn(['progress_percentage', 'progress_eta']);
        });
    }
};
