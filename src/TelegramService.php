<?php

namespace Agenta\StringService;

use Illuminate\Support\Facades\Log;

class TelegramService
{

    /**
     * Send note to developers with telegram
     *
     * @param $message
     * @return bool|void
     */
    public function send($message)
    {
        $send = config('stringservice.telegram_send');
        $token =  config('stringservice.telegram_secret');
        $chat_id = config('stringservice.telegram_chat_id');

        if (!$send | !$token | !$chat_id) {
            dd($send);
            return false;
        }

        try {
            $ids = explode(',', $chat_id);
            foreach ($ids as $id) {
                file_get_contents('https://api.telegram.org/bot' . $token . '/sendMessage?' . http_build_query(
                        [
                            'text' => $message,
                            'chat_id' => $id,
                            'parse_mode' => 'html'
                        ])
                );
            }
        } catch (\Exception $e) {
            Log::error('TelegramLog bad token/chat_id.');
            return false;
        }

        return true;
    }


}