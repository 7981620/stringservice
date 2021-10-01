<?php

/*
 * You can place your custom package configuration in here.
 */
return [

    //Отправка SMS через API SMSClub.mobi
    'sms_api_url' => 'https://gate.smsclub.mobi/http/?',
    'sms_send' => env('SEND_SMS_ENABLE', false),
    'sms_login' => env('SMS_LOGIN', null),
    'sms_password' => env('SMS_PASSWORD', null),
    'sms_alphaname' => env('SMS_ALFA', null),
    'phone_ua_codes' => '067,068,096,097,098,050,066,095,099,063,073,093,089,094',

    //Отправка в Telegram
    'telegram_send' => env('TELEGRAM_SEND_ENABLE', false),
    'telegram_secret' => env('TELEGRAM_LOGGER_TOKEN', null),
    'telegram_chat_id' => env('TELEGRAM_LOGGER_CHAT_ID', null),

];