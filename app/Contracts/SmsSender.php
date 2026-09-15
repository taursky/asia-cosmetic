<?php

namespace App\Contracts;

interface SmsSender
{
    public function send(string $phone, string $message): void;
}
