<?php

namespace App\Http\Controllers\Api\Account;

use App\Http\Controllers\Controller;
use App\Models\InvoiceRequest;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountDocumentsController extends Controller
{
    public function documents(Request $request): JsonResponse
    {
        return response()->json([
            'documents' => $request->user()->customerDocuments()->latest()->get(),
        ]);
    }

    public function requestInvoice(Request $request, Order $order): JsonResponse
    {
        abort_unless((int) $order->user_id === (int) $request->user()->id, 404);

        $profile = $request->user()->customerProfile;
        abort_unless($profile && $profile->verification_status === 'verified', 422, 'Платёжные реквизиты ещё не подтверждены.');

        $invoice = InvoiceRequest::query()->firstOrCreate([
            'user_id' => $request->user()->id,
            'order_id' => $order->id,
        ]);

        return response()->json(['invoice_request' => $invoice]);
    }
}
