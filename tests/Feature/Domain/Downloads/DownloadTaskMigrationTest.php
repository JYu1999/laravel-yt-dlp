<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Downloads;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class DownloadTaskMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function testItCreatesDownloadTasksTable(): void
    {
        self::assertTrue(Schema::hasTable('download_tasks'));
        self::assertTrue(Schema::hasColumns('download_tasks', [
            'id',
            'user_id',
            'ip_address',
            'video_url',
            'format',
            'status',
            'file_path',
            'title',
            'meta_data',
            'error_message',
            'progress_percentage',
            'progress_eta',
            'created_at',
            'updated_at',
        ]));
    }
}
