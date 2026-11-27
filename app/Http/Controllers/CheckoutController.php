<?php

namespace App\Http\Controllers;

use App\Actions\PurchaseCredits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class CheckoutController
{
    public function create(Request $request): Response
    {
        $payment = $request->string('payment')->value();

        return Inertia::render('Checkout', [
            'packs' => PurchaseCredits::PACKS,
            'payment' => $payment ? [
                'id' => $payment,
                'entry' => $request->user()->ledgerEntries()->firstWhere('reference', $payment)?->only('amount', 'balance_after'),
            ] : null,
        ]);
    }

    public function store(Request $request, PurchaseCredits $purchase): RedirectResponse
    {
        $input = $request->validate(['dollars' => ['required', 'integer', Rule::in(PurchaseCredits::PACKS)]]);

        return to_route('checkout', ['payment' => $purchase->handle($request->user(), $input['dollars'])]);
    }
}
