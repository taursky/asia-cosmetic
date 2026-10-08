<?php

namespace App\Http\Controllers\Api\OneC\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\OneC\BatchRequest;
use App\Http\Requests\OneC\ImageSyncRequest;
use App\Http\Requests\OneC\OrderStatusRequest;
use App\Models\Order;
use App\Services\OneC\CatalogSyncService;
use App\Services\OneC\CategorySyncService;
use App\Services\OneC\ImageSyncService;
use App\Services\OneC\OrderExchangeService;
use App\Services\OneC\PriceSyncService;
use App\Services\OneC\PriceTypeSyncService;
use App\Services\OneC\StockSyncService;
use App\Services\OneC\SyncLogger;
use App\Services\OneC\WarehouseSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OneCController extends Controller
{
    public function __construct(
        private readonly SyncLogger $logger,
        private readonly WarehouseSyncService $warehouses,
        private readonly CategorySyncService $categories,
        private readonly CatalogSyncService $catalog,
        private readonly PriceTypeSyncService $priceTypes,
        private readonly PriceSyncService $prices,
        private readonly StockSyncService $stocks,
        private readonly ImageSyncService $images,
        private readonly OrderExchangeService $orders,
    ) {}

    public function ping(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'service' => 'asia-cosmetic-1c',
            'version' => 'v1.4',
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
//        Log::debug('1C products', $request->all());

        return $this->batch('products', $request, fn ($items) => $this->catalog->sync($items));
    }

    public function priceTypes(BatchRequest $request): JsonResponse
    {
        return $this->batch('price_types', $request, fn ($items) => $this->priceTypes->sync($items));
    }

    public function prices(BatchRequest $request): JsonResponse
    {
        return $this->batch('prices', $request, fn ($items) => $this->prices->sync($items));
    }

    public function stocks(BatchRequest $request): JsonResponse
    {
        Log::debug('1C stocks', [$request->all()]);
        return $this->batch('stocks', $request, fn ($items) => $this->stocks->sync($items));
    }

    public function image(ImageSyncRequest $request): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'image' => $this->images->sync($request->validated()),
        ]);
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
            'order',
            '1c_to_site',
            $order->uuid,
            $request->validated(),
            fn () => $this->orders->markStatus($order, $request->validated())->toArray(),
        );

        return response()->json(['ok' => true, 'order' => $updated]);
    }

    private function batch(string $entity, BatchRequest $request, callable $callback): JsonResponse
    {
        $data = $request->validated();
        $source = trim((string) ($data['source'] ?? config('onec.source', '1c-unf'))) ?: '1c-unf';

        // Источник пакета прокидываем в каждую строку, чтобы существующие sync-сервисы
        // могли определять составной ключ source + GUID 1С.
        $items = array_map(
            static fn (array $item): array => ['source' => $item['source'] ?? $source] + $item,
            $data['items'],
        );

        $logPayload = $data;
        $logPayload['source'] = $source;
        $logPayload['items'] = $items;

        Log::debug("1C {$entity}", [
            'source' => $source,
            'exchange_id' => $data['exchange_id'] ?? null,
            'count' => count($items),
        ]);

        $result = $this->logger->run(
            $entity,
            '1c_to_site',
            $data['exchange_id'] ?? null,
            $logPayload,
            fn () => $callback($items),
        );

        $payload = is_object($result) && method_exists($result, 'toArray')
            ? $result->toArray()
            : (array) $result;

        $errors = $payload['errors'] ?? [];

        return response()->json(
            ['ok' => empty($errors), 'source' => $source] + $payload,
            empty($errors) ? 200 : 207,
        );
    }
}
