<?php

namespace Database\Factories;

use App\Domain\Downloads\Enums\DownloadStatus;
use App\Domain\Downloads\Models\DownloadTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Downloads\Models\DownloadTask>
 */
class DownloadTaskFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DownloadTask::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'ip_address' => $this->faker->ipv4,
            'video_url' => $this->faker->url,
            'format' => 'mp4',
            'status' => DownloadStatus::pending,
            'file_path' => null,
            'title' => $this->faker->sentence,
            'meta_data' => [],
            'error_message' => null,
            'progress_percentage' => 0,
            'progress_eta' => null,
        ];
    }
}
