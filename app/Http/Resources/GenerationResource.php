<?php

namespace App\Http\Resources;

use App\Enums\GenerationStatus;
use App\Models\Generation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Generation */
final class GenerationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...$this->only([
                'id', 'prompt', 'size', 'quality', 'reference_images', 'status', 'estimated_cost', 'cost', 'provider_cost',
                'usage', 'rates', 'error', 'events', 'created_at', 'started_at', 'completed_at',
            ]),
            'image' => $this->status === GenerationStatus::Succeeded ? route('generations.image', $this->resource) : null,
        ];
    }
}
