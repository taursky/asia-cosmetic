<?php

namespace App\Http\Controllers\Api\Account;

use App\Http\Controllers\Controller;
use App\Models\CustomerProfile;
use App\Models\CustomerRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        // The account UI must always receive a profile object.
        CustomerProfile::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'legal_type' => CustomerProfile::LEGAL_TYPE_INDIVIDUAL,
                'verification_status' => CustomerProfile::STATUS_DRAFT,
            ],
        );

        // Compatibility for old users created before customer_role_id existed.
        if (! $user->customer_role_id) {
            $defaultRole = CustomerRole::query()
                ->where('is_active', true)
                ->where('is_default', true)
                ->orderBy('level')
                ->first();

            if ($defaultRole) {
                $user->forceFill([
                    'customer_role_id' => $defaultRole->id,
                    'customer_role_valid_until' => $defaultRole->validity_days
                        ? now()->addDays($defaultRole->validity_days)
                        : null,
                ])->save();
            }
        }

        $user->refresh()->load([
            'customerRole.priceType',
            'customerProfile',
        ]);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'email_verified_at' => $user->email_verified_at,
                'phone_verified_at' => $user->phone_verified_at,
                'is_active' => $user->is_active,
                'last_login_at' => $user->last_login_at,
                'customer_role_id' => $user->customer_role_id,
                'customer_role_locked' => $user->customer_role_locked,
                'customer_role_valid_until' => $user->customer_role_valid_until,
                'customer_role' => $user->customerRole ? [
                    'id' => $user->customerRole->id,
                    'code' => $user->customerRole->code,
                    'name' => $user->customerRole->name,
                    'level' => $user->customerRole->level,
                    'product_price_type_id' => $user->customerRole->product_price_type_id,
                    'price_type' => $user->customerRole->priceType ? [
                        'id' => $user->customerRole->priceType->id,
                        'code' => $user->customerRole->priceType->code,
                        'name' => $user->customerRole->priceType->name,
                    ] : null,
                ] : null,
                'customer_profile' => $user->customerProfile?->toArray(),
            ],
        ]);
    }

    public function updatePersonal(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $request->user()->update($data);

        return $this->show($request);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'legal_type' => [
                'required',
                Rule::in([
                    CustomerProfile::LEGAL_TYPE_INDIVIDUAL,
                    CustomerProfile::LEGAL_TYPE_IP,
                    CustomerProfile::LEGAL_TYPE_ENTITY,
                ]),
            ],
            'company_name' => ['nullable', 'string', 'max:255'],
            'full_company_name' => ['nullable', 'string', 'max:255'],
            'inn' => ['nullable', 'digits_between:10,12'],
            'kpp' => ['nullable', 'digits:9'],
            'ogrn' => ['nullable', 'digits:13'],
            'ogrnip' => ['nullable', 'digits:15'],
            'legal_address' => ['nullable', 'string', 'max:255'],
            'actual_address' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_bik' => ['nullable', 'digits:9'],
            'bank_account' => ['nullable', 'digits:20'],
            'bank_corr_account' => ['nullable', 'digits:20'],
            'director_name' => ['nullable', 'string', 'max:255'],
            'director_position' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email:rfc', 'max:255'],
        ]);

        if ($data['legal_type'] === CustomerProfile::LEGAL_TYPE_ENTITY) {
            validator($data, [
                'company_name' => ['required'],
                'inn' => ['required', 'digits:10'],
                'kpp' => ['required', 'digits:9'],
                'ogrn' => ['required', 'digits:13'],
            ])->validate();
        }

        if ($data['legal_type'] === CustomerProfile::LEGAL_TYPE_IP) {
            validator($data, [
                'company_name' => ['required'],
                'inn' => ['required', 'digits:12'],
                'ogrnip' => ['required', 'digits:15'],
            ])->validate();
        }

        $profile = CustomerProfile::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                ...$data,
                'verification_status' => CustomerProfile::STATUS_PENDING,
                'verified_at' => null,
                // Comment belongs to the manager and is retained until the next review.
            ],
        );

        return response()->json([
            'profile' => $profile->fresh(),
        ]);
    }
}
