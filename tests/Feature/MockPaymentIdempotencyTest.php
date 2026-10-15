<?php

namespace Tests\Feature;

use App\Models\CheckoutSession;
use App\Models\Order;
use App\Models\PaymentTransaction;
use Tests\TestCase;

class MockPaymentIdempotencyTest extends TestCase
{
    public function test_repeated_mock_payment_does_not_create_duplicates(): void
    {
        if (! app()->environment('local')) {
            $this->markTestSkipped('Mock payment is available only in local.');
        }

        $session = CheckoutSession::query()
            ->where('status', 'completed')
            ->where('customer_type', 'individual')
            ->where('payment_method', 'card')
            ->whereHas('user')
            ->whereHas('items')
            ->whereHas('paymentTransactions', function ($query) {
                $query->where('provider', 'mock')
                    ->where('status', 'succeeded');
            })
            ->latest('id')
            ->first();

        $this->assertNotNull($session, 'Не найдена оплаченная тестовая сессия.');

        $ordersBefore = Order::query()
            ->where('checkout_session_uuid', $session->uuid)
            ->count();

        $transactionsBefore = PaymentTransaction::query()
            ->where('checkout_session_id', $session->id)
            ->count();

        $response = $this->actingAs($session->user)
            ->post(route('checkout.payment.mock-success', [
                'session' => $session->id,
            ]));

        $response->assertRedirect();

        $this->assertSame(
            $ordersBefore,
            Order::query()
                ->where('checkout_session_uuid', $session->uuid)
                ->count()
        );

        $this->assertSame(
            $transactionsBefore,
            PaymentTransaction::query()
                ->where('checkout_session_id', $session->id)
                ->count()
        );
    }
}
