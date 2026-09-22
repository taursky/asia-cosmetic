<?php

namespace App\Http\Controllers\Api\OneC\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\OneC\BatchRequest;
use App\Http\Requests\OneC\OrderStatusRequest;
use App\Models\Order;
use App\Services\OneC\CatalogSyncService;
use App\Services\OneC\CategorySyncService;
use App\Services\OneC\OrderExchangeService;
use App\Services\OneC\PriceSyncService;
use App\Services\OneC\StockSyncService;
use App\Services\OneC\SyncLogger;
use App\Services\OneC\WarehouseSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OneCController extends Controller
{
    public function __construct(
        private readonly SyncLogger $logger,
        private readonly WarehouseSyncService $warehouses,
        private readonly CategorySyncService $categories,
        private readonly CatalogSyncService $catalog,
        private readonly PriceSyncService $prices,
        private readonly StockSyncService $stocks,
        private readonly OrderExchangeService $orders,
    ) {}

    public function ping(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'service' => 'asia-cosmetic-1c',
            'version' => 'v1',
            'time' => now()->toIso8601String(),
        ]);
    }

    public function warehouses(BatchRequest $request): JsonResponse
    {
        return $this->batch('warehouses', $request, fn ($items) => $this->warehouses->sync($items));
    }

    public function categories(BatchRequest $request): JsonResponse
    {
        return $this->batch('categories', $request, fn ($items) => $this->categories->sync($items));
    }

    public function products(BatchRequest $request): JsonResponse
    {
        return $this->batch('products', $request, fn ($items) => $this->catalog->sync($items));
    }

    public function prices(BatchRequest $request): JsonResponse
    {
        return $this->batch('prices', $request, fn ($items) => $this->prices->sync($items));
    }

    public function stocks(BatchRequest $request): JsonResponse
    {
        return $this->batch('stocks', $request, fn ($items) => $this->stocks->sync($items));
    }

    public function orders(Request $request): JsonResponse
    {
        $paginator = $this->orders->pending((int) $request->integer('per_page', 100));
        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function orderStatus(OrderStatusRequest $request, Order $order): JsonResponse
    {
        $updated = $this->logger->run(
            'order', '1c_to_site', $order->uuid, $request->validated(),
            fn () => $this->orders->markStatus($order, $request->validated())->toArray()
        );

        return response()->json(['ok' => true, 'order' => $updated]);
    }

    private function batch(string $entity, BatchRequest $request, callable $callback): JsonResponse
    {
        $data = $request->validated();

        $result = $this->logger->run(
            $entity,
            '1c_to_site',
            $data['exchange_id'] ?? null,
            $data,
            fn () => $callback($data['items']),
        );

        $payload = is_object($result) && method_exists($result, 'toArray')
            ? $result->toArray()
            : (array) $result;

        $errors = $payload['errors'] ?? [];

        return response()->json(
            ['ok' => empty($errors)] + $payload,
            empty($errors) ? 200 : 207,
        );
    }

}
