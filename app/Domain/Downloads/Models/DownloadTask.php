<?php

declare(strict_types=1);

namespace App\Domain\Downloads\Models;

use App\Domain\Downloads\Enums\DownloadStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DownloadTask extends Model
{
    /** @use HasFactory<\Database\Factories\DownloadTaskFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'ip_address',
        'video_url',
        'format',
        'status',
        'file_path',
        'title',
        'meta_data',
        'error_message',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta_data' => 'array',
            'status' => DownloadStatus::class,
        ];
    }
}
