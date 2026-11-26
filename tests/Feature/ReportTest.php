<?php

use App\Enums\GenerationStatus;
use App\Models\Generation;
use App\Models\User;
use Illuminate\Support\Facades\Http;

it('reports spend, provider cost and profit per day and user, reconciled with Pixelforge', function () {
    $this->travelTo('2026-11-27 12:00:00');
    $alice = User::factory()->create(['name' => 'Alice']);
    $succeeded = ['status' => GenerationStatus::Succeeded, 'provider_cost' => 300, 'cost' => 375];

    Generation::factory()->for($alice)->create([...$succeeded, 'completed_at' => '2026-11-26 23:59:59']);
    Generation::factory()->for($alice)->create([...$succeeded, 'completed_at' => '2026-11-27 00:00:01', 'created_at' => '2026-11-26 23:59:00']);
    Generation::factory()->for($alice)->create(['status' => GenerationStatus::Failed, 'provider_cost' => 0, 'cost' => 0, 'completed_at' => '2026-11-27 01:00:00']);

    Http::fake(['*/v1/usage*' => Http::response(['data' => [
        ['date' => '2026-11-26', 'generations' => 1, 'cost_usd' => '0.000000300'],
        ['date' => '2026-11-27', 'generations' => 1, 'cost_usd' => '0.000000300'],
    ]])]);

    $this->actingAs(User::factory()->operator()->create())->get('/admin?days=7')
        ->assertInertia(fn ($page) => $page->component('Admin')
            ->where('report.reconciled', true)
            ->where('report.days.0', ['day' => '2026-11-27', 'generations' => 1, 'charged' => 375, 'provider_cost' => 300, 'profit' => 75, 'pixelforge' => 300])
            ->where('report.days.1.day', '2026-11-26')
            ->where('report.users.0.user.name', 'Alice')
            ->where('report.users.0.profit', 150)
            ->where('report.totals.pixelforge', 600));
});

it('is only available to operators', function () {
    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
});
