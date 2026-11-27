<?php

use App\Enums\LedgerType;
use App\Jobs\DeliverPaymentWebhook;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

function paymentWebhook(array $payment, ?string $secret = null): TestResponse
{
    $body = json_encode(['id' => 'evt_pay', 'type' => 'payment.succeeded', 'data' => $payment]);

    return test()->call('POST', '/webhooks/payments', server: [
        'HTTP_PAYMENTS_SIGNATURE' => webhookHeaders($body, 'Payments', secret: $secret)['Payments-Signature'],
        'CONTENT_TYPE' => 'application/json',
    ], content: $body);
}

it('starts a checkout and lets the processor confirm it by webhook', function () {
    Bus::fake();
    $user = User::factory()->create();

    $this->actingAs($user)->post('/credits/checkout', ['dollars' => 10])->assertRedirectContains('/credits/checkout?payment=pay_');

    Bus::assertDispatched(DeliverPaymentWebhook::class, fn ($job) => $job->payment['amount'] === 10 * DOLLAR && $job->payment['user_id'] === $user->id);
    expect($user->fresh()->balance)->toBe(0);
});

it('signs the simulated processor webhook', function () {
    Http::fake();

    (new DeliverPaymentWebhook(['id' => 'pay_1', 'user_id' => 1, 'amount' => DOLLAR]))->handle();

    Http::assertSent(function (Request $request) {
        [$t, $v1] = explode(',', $request->header('Payments-Signature')[0]);

        return $request->url() === 'http://emberly.test/webhooks/payments'
            && hash_equals(hash_hmac('sha256', substr($t, 2).'.'.$request->body(), 'payments_test'), substr($v1, 3));
    });
});

it('credits a payment exactly once', function () {
    $user = User::factory()->create();
    $payment = ['id' => 'pay_1', 'user_id' => $user->id, 'amount' => 25 * DOLLAR];

    paymentWebhook($payment)->assertNoContent();
    paymentWebhook($payment)->assertNoContent();

    expect($user->fresh()->balance)->toBe(25 * DOLLAR)
        ->and($user->ledgerEntries()->sole()->type)->toBe(LedgerType::Purchase);
});

it('ignores payment webhooks with a bad signature', function () {
    $user = User::factory()->create();

    paymentWebhook(['id' => 'pay_1', 'user_id' => $user->id, 'amount' => DOLLAR], secret: 'forged')->assertStatus(400);

    expect($user->fresh()->balance)->toBe(0);
});

it('shows the confirmation once the webhook has credited the account', function () {
    $user = User::factory()->withCredits(5 * DOLLAR)->create();

    $this->actingAs($user)->get('/credits/checkout?payment=pay_seed_'.$user->id)
        ->assertInertia(fn ($page) => $page->component('Checkout')->where('payment.entry.amount', 5 * DOLLAR));
});
