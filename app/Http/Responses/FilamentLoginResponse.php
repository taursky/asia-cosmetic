<?php

namespace App\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;

class FilamentLoginResponse implements LoginResponse
{
    public function toResponse($request): RedirectResponse
    {
        return redirect()->intended(Filament::getUrl());
    }
}
