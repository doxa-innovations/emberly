<?php

namespace App\Http\Requests;

use App\Support\Pixelforge;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StartGenerationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'max:4000'],
            'size' => ['required', Rule::in(Pixelforge::SIZES)],
            'quality' => ['required', Rule::in(Pixelforge::QUALITIES)],
            'reference_images' => ['array', 'max:4'],
            'reference_images.*' => ['required', 'url:http,https', 'max:2048', 'distinct'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing(['reference_images' => []]);
    }
}
