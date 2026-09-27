<?php

namespace App\Http\Controllers\Api\Account;

use App\Http\Controllers\Controller;
use App\Models\CustomerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load([
            'customerRole.priceType',
            'customerProfile',
        ]);

        return response()->json([
            'user' => $user,
        ]);
    }

    public function updatePersonal(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $user->update($data);

        return response()->json([
            'user' => $user->fresh()->load('customerRole.priceType'),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'legal_type' => [
                'required',
                Rule::in(['individual', 'individual_entrepreneur', 'legal_entity']),
            ],
            'company_name' => ['nullable', 'string', 'max:255'],
            'full_company_name' => ['nullable', 'string', 'max:255'],
            'inn' => ['nullable', 'digits_between:10,12'],
            'kpp' => ['nullable', 'digits:9'],
            'ogrn' => ['nullable', 'digits_between:13,15'],
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

        if ($data['legal_type'] === 'legal_entity') {
            validator($data, [
                'company_name' => ['required'],
                'inn' => ['required', 'digits:10'],
                'kpp' => ['required', 'digits:9'],
                'ogrn' => ['required', 'digits:13'],
            ])->validate();
        }

        if ($data['legal_type'] === 'individual_entrepreneur') {
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
                'verification_status' => 'pending',
                'verified_at' => null,
                'verification_comment' => null,
            ],
        );

        return response()->json([
            'profile' => $profile->fresh(),
        ]);
    }
}
