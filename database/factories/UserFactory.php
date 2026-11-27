<?php

namespace Database\Factories;

use App\Enums\LedgerType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'google_id' => (string) fake()->unique()->randomNumber(9),
        ];
    }

    public function operator(): static
    {
        return $this->state(['is_operator' => true, 'password' => 'secret-password']);
    }

    public function withCredits(int $nanos): static
    {
        return $this->afterCreating(fn (User $user) => $user->ledgerEntries()->create([
            'type' => LedgerType::Purchase,
            'amount' => $nanos,
            'reference' => 'pay_seed_'.$user->id,
        ]));
    }
}
