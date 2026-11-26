<?php

namespace App\Actions;

use App\Enums\GenerationStatus;
use App\Enums\LedgerType;
use App\Jobs\SubmitGeneration;
use App\Models\Generation;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class StartGeneration
{
    private const MAX_IN_FLIGHT = 4;

    public function __construct(private QuoteGeneration $quote) {}

    public function handle(User $user, array $input): Generation
    {
        $quote = $this->quote->handle(
            mb_strlen($input['prompt']), $input['size'], $input['quality'], count($input['reference_images']),
        );

        $generation = DB::transaction(function () use ($user, $input, $quote) {
            $this->ensureAllowed($user, $quote['maximum']);

            $generation = $user->generations()->create([
                ...$input,
                'status' => GenerationStatus::Queued,
                'estimated_cost' => $quote['estimate'],
            ]);

            $generation->ledgerEntries()->create([
                'user_id' => $user->id,
                'type' => LedgerType::Hold,
                'amount' => -$quote['maximum'],
            ]);

            return $generation;
        });

        SubmitGeneration::dispatch($generation);

        return $generation;
    }

    private function ensureAllowed(User $user, int $maximum): void
    {
        $balance = User::whereKey($user->id)->lockForUpdate()->value('balance');

        if ($balance < $maximum) {
            throw ValidationException::withMessages([
                'prompt' => 'Not enough credits: this can cost up to '.Money::format($maximum).' and you have '.Money::format($balance).' available.',
            ]);
        }

        $inFlight = $user->generations()->whereIn('status', [GenerationStatus::Queued, GenerationStatus::Processing])->count();

        if ($inFlight >= self::MAX_IN_FLIGHT) {
            throw ValidationException::withMessages([
                'prompt' => 'You already have '.self::MAX_IN_FLIGHT.' generations in progress. Wait for one to finish.',
            ]);
        }
    }
}
