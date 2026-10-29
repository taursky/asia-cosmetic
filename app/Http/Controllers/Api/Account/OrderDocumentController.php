<?php

namespace App\Http\Controllers\Api\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderDocumentController extends Controller
{
    private const FILES = [
        'invoice' => ['file' => 'invoice.pdf', 'label' => 'Счёт'],
        'contract' => ['file' => 'contract.pdf', 'label' => 'Договор'],
    ];

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'max:100'],
            'ids.*' => ['required', 'integer', 'min:1'],
        ]);

        $orders = Order::query()
            ->where('user_id', $request->user('web')->getAuthIdentifier())
            ->whereIn('id', $data['ids'])
            ->get(['id', 'uuid']);

        $result = [];
        foreach ($orders as $order) {
            $result[$order->id] = $this->documentsFor($order);
        }

        return response()->json(['documents' => $result]);
    }

    public function show(Request $request, Order $order, string $type): StreamedResponse
    {
        abort_unless((string) $order->user_id === (string) $request->user('web')->getAuthIdentifier(), 404);
        abort_unless(isset(self::FILES[$type]), 404);

        $path = $this->path($order, $type);
        abort_unless($path && Storage::disk('public')->exists($path), 404);

        $disposition = $request->query('action') === 'print' ? 'inline' : 'attachment';
        $filename = self::FILES[$type]['file'];

        return Storage::disk('public')->response($path, $filename, [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ], $disposition);
    }

    private function documentsFor(Order $order): array
    {
        $documents = [];
        foreach (self::FILES as $type => $definition) {
            $path = $this->path($order, $type);
            if (!$path || !Storage::disk('public')->exists($path)) {
                continue;
            }
            $url = route('account.orders.documents.show', ['order' => $order->id, 'type' => $type]);
            $documents[] = [
                'type' => $type,
                'label' => $definition['label'],
                'download_url' => $url,
                'print_url' => $url . '?action=print',
            ];
        }
        return $documents;
    }

    private function path(Order $order, string $type): ?string
    {
        if (!isset(self::FILES[$type]) || !$order->uuid) {
            return null;
        }
        return 'invoices/' . $order->uuid . '/' . self::FILES[$type]['file'];
    }
}
