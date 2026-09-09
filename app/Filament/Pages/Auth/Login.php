<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;

class Login extends BaseLogin
{
    // Используем штатную аутентификацию Filament.
    // Модель определяется provider'ом guard `admin`, а не ручным запросом к БД.
}
