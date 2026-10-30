<?php

namespace App\Services\OneC;

use App\Models\Order;

final class OrderCustomerData
{
    /** Build an explicit customer payload from the order snapshot, never from the current user profile. */
    public function forOrder(Order $order): array
    {
        $source = is_array($order->customer_data) ? $order->customer_data : [];
        $type = $order->customer_type;

        if ($type === 'individual') {
            $profile = is_array($source['profile'] ?? null) ? $source['profile'] : [];

            return array_filter([
                'name' => $source['name'] ?? null,
                'last_name' => $source['last_name'] ?? ($profile['last_name'] ?? null),
                'first_name' => $source['first_name'] ?? ($profile['first_name'] ?? null),
                'middle_name' => $source['middle_name'] ?? ($profile['middle_name'] ?? null),
                'phone' => $source['phone'] ?? ($profile['contact_phone'] ?? null),
                'email' => $source['email'] ?? ($profile['contact_email'] ?? null),
            ], static fn ($value) => $value !== null && $value !== '');
        }

        if ($type !== 'legal' && $type !== 'entrepreneur' && $type !== 'ip') {
            throw new \UnexpectedValueException("Unsupported order customer_type: {$type}");
        }

        // Legal/IP orders retain the immutable customer snapshot, including requisites.
        // Do not replace it with the user's current profile.
        return $source;
    }
}
