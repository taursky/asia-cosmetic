<?php

namespace App\Http\Controllers\Api\Account;

use App\Http\Controllers\Controller;
use App\Models\CustomerProfile;
use App\Services\Counterparties\CounterpartyDirectoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ApiFnsController extends Controller
{
    public function suggest(Request $request, CounterpartyDirectoryService $directory): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'max:255'],
        ]);

        return response()->json([
            'items' => $directory->suggest($data['q'], $request->user()?->id),
        ]);
    }

    public function lookup(Request $request, CounterpartyDirectoryService $directory): JsonResponse
    {
        $data = $request->validate([
            'req' => ['required', 'regex:/^\d{10,15}$/'],
        ]);

        $result = $directory->lookup($data['req'], $request->user()?->id);

        if (! $result) {
            throw ValidationException::withMessages([
                'fns' => 'Контрагент не найден.',
            ]);
        }

        return response()->json([
            'profile' => $result['profile'],
            'source' => $result['source'],
            'fetched_at' => $result['fetched_at'],
        ]);
    }

    public function verify(Request $request, CounterpartyDirectoryService $directory): JsonResponse
    {
        $profile = CustomerProfile::query()->firstOrCreate(
            ['user_id' => $request->user()->id],
            ['legal_type' => 'individual', 'verification_status' => 'draft'],
        );

        $req = $profile->inn ?: ($profile->ogrn ?: $profile->ogrnip);

        if (! $req) {
            throw ValidationException::withMessages([
                'fns' => 'Сначала укажите ИНН или ОГРН/ОГРНИП.',
            ]);
        }

        $company = $directory->lookup($req, $request->user()->id, forceRemote: false);

        if (! $company) {
            throw ValidationException::withMessages(['fns' => 'Контрагент не найден.']);
        }

        $mismatches = $directory->compare($profile->toArray(), $company['profile']);

        $profile->forceFill([
            'fns_status' => $company['profile']['fns_status'] ?? null,
            'fns_checked_at' => now(),
            'fns_data' => $company['raw'],
            'fns_check_data' => [
                'source' => $company['source'],
                'cached' => $company['source'] === 'mongodb',
            ],
        ])->save();

        return response()->json([
            'ok' => empty($mismatches),
            'mismatches' => $mismatches,
            'official_profile' => $company['profile'],
            'source' => $company['source'],
            'checked_at' => $profile->fns_checked_at,
        ]);
    }
}
