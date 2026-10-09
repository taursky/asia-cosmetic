<?php

namespace App\Http\Controllers\Api\Cart;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Services\Cart\CartPricingService;
use App\Services\Cart\CartService;
use App\Services\Cart\CheckoutService;
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

    public function show(Request $request, CartService $carts, CartPricingService $pricing): View
    {
        $cart = $carts->cartFor($request->user());

        return view('checkout.index', [
            'cart' => $pricing->calculate($cart),
            'user' => $request->user()->load('customerProfile', 'customerRole'),
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

        $order = $checkout->checkout(
            $carts->cartFor($request->user()),
            $data,
        );

        return redirect()->route('checkout.success', $order);
    }

    public function success(Request $request, Order $order): View
    {
        abort_unless((int) $order->user_id === (int) $request->user()->id, 404);

        return view('checkout.success', compact('order'));
    }
}
