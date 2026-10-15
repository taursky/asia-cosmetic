<?php

namespace App\Http\Controllers\Api\Cart;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CheckoutSession;
use App\Models\Order;
use App\Services\Cart\CartPricingService;
use App\Services\Cart\CartService;
use App\Services\Cart\CheckoutService;
use App\Services\Orders\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function __invoke(Request $request, CheckoutService $checkout): JsonResponse
    {
        $user = $request->user();
        $cart = Cart::query()->where('user_id', $user->id)->firstOrFail();
        $order = $checkout->checkout($cart, $request->only([
            'delivery_method', 'delivery_address', 'payment_method', 'comment',
        ]));

        return response()->json(['order' => $order], 201);
    }

    public function show(Request $request, CartService $carts, CartPricingService $pricing): View|RedirectResponse
    {
        $cart = $carts->cartFor($request->user());
        $selectedIds = $request->session()->get(
            'checkout.selected_item_ids',
            []
        );

        if (empty($selectedIds)) {
            return redirect()->route('cart.index');
        }

        $result = $pricing->calculate($cart, $selectedIds);

        if (empty($result['items'])) {
            throw ValidationException::withMessages([
                'cart' => 'Выбранные товары отсутствуют в корзине.',
            ]);
        }

        return view('checkout.index', [
            'cart' => $result,
            'user' => $request->user()->load(
                'customerProfile',
                'customerRole'
            ),
        ]);
    }

    public function store(Request $request, CartService $carts, CheckoutService $checkout): RedirectResponse
    {
        $data = $request->validate([
            'delivery_method' => ['required', 'in:pickup,delivery'],
            'delivery_address' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['required', 'in:invoice,card'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['delivery_method'] === 'delivery' && empty($data['delivery_address'])) {
            return back()->withErrors(['delivery_address' => 'Укажите адрес доставки.'])->withInput();
        }

        $selectedIds = $request->session()->get('checkout.selected_item_ids', []);

        if (empty($selectedIds)) {
            throw ValidationException::withMessages([
                'cart' => 'Не выбраны товары для оформления.',
            ]);
        }

        $result = $checkout->checkout(
            $carts->cartFor($request->user()),
            $data,
            $selectedIds,
        );

        $request->session()->forget('checkout.selected_item_ids');

        if ($result instanceof \App\Models\CheckoutSession) {
            return redirect()->route('checkout.payment', [
                'session' => $result->id,
            ]);
        }

        return redirect()->route('checkout.success', [
            'order' => $result->id,
        ]);
    }

    public function payment(Request $request, CheckoutSession $session)
    {
        abort_unless(
            $session->user_id !== null
            && (int) $session->user_id === (int) $request->user()->id,
            404
        );

        abort_unless(
            $session->customer_type === 'individual'
            && $session->payment_method === 'card',
            404
        );

        return view('checkout.payment', [
            'session' => $session,
            'mockEnabled' => app()->environment('local'),
        ]);
    }

    public function mockPaymentSuccess(Request $request, CheckoutSession $session, OrderService $orders)
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        abort_unless(
            $session->user_id !== null
            && (int) $session->user_id === (int) $request->user()->id,
            404
        );

        abort_unless(
            $session->customer_type === 'individual'
            && $session->payment_method === 'card',
            404
        );

        $order = \Illuminate\Support\Facades\DB::transaction(
            function () use ($session, $orders) {
                $session = \App\Models\CheckoutSession::query()
                    ->whereKey($session->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Повторное подтверждение уже оплаченной сессии.
                $existingOrder = \App\Models\Order::query()
                    ->where('checkout_session_uuid', $session->uuid)
                    ->first();

                if ($existingOrder) {
                    abort_unless($existingOrder->payment_status === 'paid', 409);

                    return $existingOrder;
                }

                abort_unless(
                    $session->status === 'pending_payment'
                    && $session->expires_at !== null
                    && $session->expires_at->isFuture(),
                    409,
                    'Платёжная сессия недействительна или срок её действия истёк.'
                );

                $transaction = \App\Models\PaymentTransaction::query()
                    ->where('checkout_session_id', $session->id)
                    ->where('type', 'payment')
                    ->lockForUpdate()
                    ->first();

                if ($transaction === null) {
                    $transaction = \App\Models\PaymentTransaction::query()->create([
                        'uuid' => (string) \Illuminate\Support\Str::uuid(),
                        'checkout_session_id' => $session->id,
                        'provider' => 'mock',
                        'type' => 'payment',
                        'status' => 'succeeded',
                        'external_id' => 'MOCK-' . $session->uuid,
                        'idempotency_key' => 'mock-payment-' . $session->uuid,
                        'amount' => $session->total,
                        'currency' => $session->currency,
                        'confirmed_at' => now(),
                    ]);
                } else {
                    abort_unless(
                        $transaction->provider === 'mock'
                        && $transaction->status === 'succeeded'
                        && $transaction->confirmed_at !== null,
                        409,
                        'Существующая платёжная транзакция требует проверки.'
                    );
                }

                return $orders->createPaidOrder($session, $transaction);
            }
        );

        return redirect()->route('checkout.success', [
            'order' => $order->id,
        ]);
    }

    public function mockPaymentFailure(Request $request,CheckoutSession $session): RedirectResponse
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        abort_unless(
            $session->user_id !== null
            && (int) $session->user_id === (int) $request->user()->id,
            404
        );

        abort_unless(
            $session->customer_type === 'individual'
            && $session->payment_method === 'card',
            404
        );

        abort_unless(
            $session->status === 'pending_payment'
            && $session->expires_at !== null
            && $session->expires_at->isFuture(),
            409
        );

        return redirect()
            ->route('checkout.payment', ['session' => $session->id])
            ->with('payment_error', 'Тестовая оплата отклонена. Попробуйте ещё раз.');
    }

    public function success(Request $request, Order $order): View
    {
        abort_unless((int) $order->user_id === (int) $request->user()->id, 404);

        return view('checkout.success', compact('order'));
    }

    public function selectItems(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['required', 'integer', 'distinct', 'min:1'],
        ]);

        $cart = Cart::query()
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $selectedIds = array_map('intval', $data['item_ids']);

        $existingCount = $cart->items()
            ->whereIn('id', $selectedIds)
            ->count();

        if ($existingCount !== count($selectedIds)) {
            throw ValidationException::withMessages([
                'item_ids' => 'Некоторые выбранные товары отсутствуют в корзине.',
            ]);
        }

        $request->session()->put('checkout.selected_item_ids', $selectedIds);

        return redirect()->route('checkout.index');
    }
}
