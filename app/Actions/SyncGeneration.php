<?php

namespace App\Actions;

use App\Enums\GenerationStatus;
use App\Enums\LedgerType;
use App\Jobs\StoreGenerationImage;
use App\Models\Generation;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SyncGeneration
{
    public function handle(array $snapshot, ?array $event = null): ?Generation
    {
        $id = data_get($snapshot, 'metadata.generation_id');

        if (! Str::isUuid($id)) {
            return null;
        }

        return DB::transaction(function () use ($id, $snapshot, $event) {
            $generation = Generation::lockForUpdate()->find($id);

            if (! $generation) {
                return null;
            }

            $isDuplicate = $event && collect($generation->events)->contains('id', $event['id']);
            $status = GenerationStatus::from($snapshot['status']);

            if ($event) {
                $generation->events = [...$generation->events, [...$event, 'received_at' => now()->toIso8601ZuluString()]];
            }

            $generation->provider_id ??= $snapshot['id'];
            $generation->rates ??= $snapshot['rates'] ?? null;

            if (! $isDuplicate && $status->advances($generation->status)) {
                $this->advance($generation, $status, $snapshot);
            }

            $generation->save();

            return $generation;
        });
    }

    private function advance(Generation $generation, GenerationStatus $status, array $snapshot): void
    {
        $generation->status = $status;
        $generation->started_at ??= $snapshot['started_at'] ?? null;

        match ($status) {
            GenerationStatus::Succeeded => $this->succeed($generation, $snapshot),
            GenerationStatus::Failed => $this->fail($generation, $snapshot),
            default => null,
        };
    }

    private function succeed(Generation $generation, array $snapshot): void
    {
        $providerCost = Money::forUsage($snapshot['usage'], $snapshot['rates']);

        $generation->fill([
            'usage' => $snapshot['usage'],
            'image_url' => $snapshot['image_url'],
            'completed_at' => $snapshot['completed_at'],
            'provider_cost' => $providerCost,
            'cost' => Money::withMarkup($providerCost),
        ]);

        $this->settle($generation, $generation->cost);

        StoreGenerationImage::dispatch($generation);
    }

    private function fail(Generation $generation, array $snapshot): void
    {
        $generation->fill([
            'error' => $snapshot['error'],
            'completed_at' => $snapshot['completed_at'] ?? now(),
            'provider_cost' => 0,
            'cost' => 0,
        ]);

        $this->settle($generation, 0);
    }

    private function settle(Generation $generation, int $charge): void
    {
        $hold = $generation->ledgerEntries()->where('type', LedgerType::Hold)->value('amount');

        $generation->ledgerEntries()->create([
            'user_id' => $generation->user_id,
            'type' => LedgerType::Release,
            'amount' => -$hold,
        ]);

        if ($charge > 0) {
            $generation->ledgerEntries()->create([
                'user_id' => $generation->user_id,
                'type' => LedgerType::Charge,
                'amount' => -$charge,
            ]);
        }
    }
}
