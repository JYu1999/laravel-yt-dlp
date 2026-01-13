<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class VideoInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'url' => [
                'required',
                'url',
                'regex:/^https?:\\/\\/(www\\.)?(youtube\\.com|youtu\\.be)\\//i',
            ],
        ];
    }
}
