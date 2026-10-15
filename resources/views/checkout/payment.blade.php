<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Оплата заказа — Asia Cosmetic</title>
</head>
<body>
<main style="max-width: 600px; margin: 60px auto; font-family: sans-serif;">
    <h1>Оплата заказа</h1>

    <p>Номер сессии: {{ $session->uuid }}</p>
    <p>Сумма: {{ number_format((float) $session->total, 2, ',', ' ') }} ₽</p>
    <p>Статус: {{ $session->status }}</p>

    @if (session('payment_error'))
        <p style="color: #b91c1c; font-weight: bold;">
            {{ session('payment_error') }}
        </p>
    @endif

    @if ($session->status === 'pending_payment')
        @if ($session->expires_at?->isPast())
            <p>Срок действия платёжной сессии истёк.</p>
        @elseif ($mockEnabled)
            <form method="POST"
                  action="{{ route('checkout.payment.mock-success', ['session' => $session->id]) }}">
                @csrf

                <button type="submit">
                    Подтвердить тестовую оплату
                </button>
            </form>

            <form method="POST"
                  action="{{ route('checkout.payment.mock-failure', ['session' => $session->id]) }}"
                  style="margin-top: 15px;">
                @csrf

                <button type="submit" style="color: #b91c1c;">
                    Имитировать отказ в оплате
                </button>
            </form>
        @else
            <p>Подключение банковской оплаты находится в разработке.</p>
        @endif
    @elseif ($session->status === 'completed')
        <p>Оплата подтверждена.</p>
    @else
        <p>Оплата недоступна для текущего состояния сессии.</p>
    @endif
</main>
</body>
</html>
