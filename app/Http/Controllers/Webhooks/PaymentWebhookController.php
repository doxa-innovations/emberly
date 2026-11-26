<?php

namespace App\Http\Controllers\Webhooks;

use App\Actions\ConfirmPayment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class PaymentWebhookController
{
    public function __invoke(Request $request, ConfirmPayment $confirm): Response
    {
        if ($request->input('type') === 'payment.succeeded') {
            $confirm->handle($request->input('data'));
        }

        return response()->noContent();
    }
}
