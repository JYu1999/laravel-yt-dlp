<?php

declare(strict_types=1);

namespace App\Domain\Downloads\Models;

use App\Domain\Downloads\Enums\DownloadStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'progress_percentage',
        'progress_eta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta_data' => 'array',
            'status' => DownloadStatus::class,
            'progress_percentage' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
