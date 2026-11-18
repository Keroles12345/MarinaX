<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, string $provider, PaymentWebhookService $webhooks): JsonResponse
    {
        return response()->json($webhooks->handle($request, $provider));
    }
}
