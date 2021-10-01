<?php

use Agenta\StringService\SmsService;
use Illuminate\Support\Facades\Route;

Route::post('/sms_confirmation', [SmsService::class, 'smsConfirmation'])
    ->middleware(['auth:web'])
    ->name('sms.confirmation');
