<?php

namespace App\Providers;

use App\Contracts\SmsSender;
use App\Services\Sms\LogSmsSender;
use Illuminate\Support\ServiceProvider;

class CustomerAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            SmsSender::class,
            LogSmsSender::class,
        );

//        $this->app->bind(SmsSender::class, function () {
//            return match (config('customer_auth.sms.driver')) {
//                'log' => app(LogSmsSender::class),
//                'smsru' => app(SmsRuSender::class),
//                'smsc' => app(SmscSender::class),
//                default => app(LogSmsSender::class),
//            };
//        });
    }
}
