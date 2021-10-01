<?php

namespace Agenta\StringService;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SmsService
{

    protected $smsMaxRetry = 3;
    protected $smsSendMaxCount = 3;
    protected $smsSendPauseSeconds = 20;
    protected $smsCodeLenght = 4;

    /**
     * Отправка SMS через SmsClub
     *
     * @param string $phone
     * @param string $message
     * @return bool
     */
    public function sendSms(string $phone, string $message): bool
    {

        $apiUrl = config('stringservice.sms_api_url');
        $sendSMS = config('stringservice.sms_send');
        $login = config('stringservice.sms_login');
        $password = config('stringservice.sms_password');
        $alphaname = config('stringservice.sms_alphaname');

        if ($sendSMS && $apiUrl && $login && $password && $alphaname && $phone && $message) {

            $message = iconv("utf-8", "windows-1251", trim($message));
            $message = substr($message, 0, 70);
            $message = urlencode($message);
            $url_result = $apiUrl . 'username=' . $login . '&password=' . $password . '&from=' . urlencode($alphaname) . '&to=' . $phone . '&text=' . $message;
            try {
                if ($curl = curl_init()) {
                    curl_setopt($curl, CURLOPT_URL, $url_result);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_exec($curl);
                    curl_close($curl);
                } else {
                    Log::error('function sendSms: curl_init() error');
                    return false;
                }
            } catch (\Exception $exception) {
                Log::error('function sendSms: ' . $exception->getMessage());
                return false;
            }

            return true;

        }


        return false;

    }

    /**
     * Проверка введенного кода подтверждения
     *
     * @param User $user
     * @param string $sms_code
     * @return array
     */
    public function smsConfirmation(User $user, string $sms_code)
    {


        if ($this->checkIsConfirmed($user)) {
            return [
                'status' => 'error',
                'description' => [
                    'type' => 'already_confirmed',
                    'value' => null,
                    'message' => 'Номер уже был подтвержден ' . Carbon::parse($user->phone_verified_at)->format('d.m.Y в H:i:s')
                ]
            ];
        }


        if(!$user->sms_code) {
            return [
                'status' => 'error',
                'description' => [
                    'type' => 'no_sms_code',
                    'value' => null,
                    'message' => 'Код смс не найден у пользователя'
                ]
            ];
        }

        //проверка, что код есть и не исчерпано время на ввод и попытки
        if (!$this->checkIsMaxRetry($user)) {


            if ($this->checkCodeLenght($sms_code)) {

                if ($user->sms_code === $sms_code) {

                    $user->sms_code = null;
                    $user->phone_verified_at = now()->toDateTimeString();
                    $user->sms_confirm_retry = null;
                    $user->sms_send_count = null;
                    $user->sms_repeat_at = null;
                    $user->sms_sended_at = null;
                    $user->save();

                    return [
                        'status' => 'success',
                        'description' => [
                            'type' => 'phone_verified',
                            'value' => null,
                            'message' => 'Успешное подтверждение номера телефона'
                        ]
                    ];

                }

                //ввели неправильный код потдверждения
                $user->sms_confirm_retry++;
                $user->save();

                $countRemaining = $this->smsMaxRetry - $user->sms_confirm_retry;

                return [
                    'status' => 'error',
                    'description' => [
                        'type' => 'sms_code_empty',
                        'value' => $countRemaining,
                        'message' => 'Вы ввели неправильный код, осталось ' . $countRemaining
                    ]
                ];

            }

            return [
                'status' => 'error',
                'description' => [
                    'type' => 'sms_code_empty',
                    'value' => null,
                    'message' => 'Указан пустое значение в коде sms'
                ]
            ];


        }

        //попытки исчерапаны
        //проверим, что еще можно отправить новое SMS
        if ($user->sms_send_count < $this->smsSendMaxCount) {

            return [
                'status' => 'error',
                'description' => [
                    'type' => 'limit_retry',
                    'value' => null,
                    'message' => 'Количество попыток ввода текущего кода исчерпано - вы должны получить новый код'
                ]
            ];

        }

        //исчерпано кол-во отправок SMS
        return [
            'status' => 'error',
            'description' => [
                'type' => 'send_limit',
                'value' => $user->sms_send_count,
                'message' => 'Достигнут лимит отправки кодов SMS'
            ]
        ];


    }

    /**
     * Отправка кода SMS
     *
     * @throws \Exception
     */
    public function sendCode(User $user)
    {

        if (is_null($user->sms_verified_at)) {

            //уже были коды?
            if (!is_null($user->sms_code)) {

                //исчерпаны отправки SMS?
                if (!$this->checkIsMaxSend($user)) {
                    //нет, отправляю код
                    $timeLimit = $this->isRestrictedSendByTimelimit($user);

                    if ($timeLimit <= 0) {
                        $code = $this->sendCodeToPhone($user);
                        return [
                            'status' => 'success',
                            'description' => [
                                'type' => 'sended',
                                'value' => $code,
                                'message' => 'Код подтверждения успешно отправлен'
                            ]
                        ];
                    }

                    return [
                        'status' => 'error',
                        'description' => [
                            'type' => 'wait_before_send',
                            'value' => $timeLimit,
                            'message' => 'Подождите ' . $timeLimit . ' сек. перед отправкой нового кода'
                        ]
                    ];

                }

                //да, исчерпаны
                return [
                    'status' => 'error',
                    'description' => [
                        'type' => 'send_limit',
                        'value' => $user->sms_send_count,
                        'message' => 'Достигнут лимит отправки кодов SMS'
                    ]
                ];

            }

            //нет, отправляю самый первый
            if ($code = $this->sendCodeToPhone($user)) {
                return [
                    'status' => 'success',
                    'description' => [
                        'type' => 'sended',
                        'value' => $code,
                        'message' => 'Код подтверждения успешно отправлен'
                    ]
                ];
            }

            return [
                'status' => 'error',
                'description' => [
                    'type' => 'not_sended',
                    'value' => null,
                    'message' => 'Код не отправлен'
                ]
            ];

        }

        return [
            'status' => 'error',
            'description' => [
                'type' => 'already_confirmed',
                'value' => null,
                'message' => 'Номер уже был подтвержден ' . Carbon::parse($user->phone_verified_at)->format('d.m.Y в H:i:s')
            ]
        ];

    }


    /**
     * Проверяет что номер еще не подтвержден
     *
     * @param User $user
     * @return bool
     */
    private function checkIsConfirmed(User $user): bool
    {
        if (!is_null($user->phone_verified_at)) {
            return true;
        }

        return false;
    }


    /**
     * Проверка что не исчерпан лимит на попытку ввода кода
     *
     * @param User $user
     * @return bool
     */
    private function checkIsMaxRetry(User $user): bool
    {
        if ($user->sms_confirm_retry) {
            return $user->sms_confirm_retry <= $this->smsMaxRetry;
        }

        return false;

    }

    /**
     * Проверка лимита на отправку SMS
     *
     * @param User $user
     * @return bool
     */
    private function checkIsMaxSend(User $user): bool
    {

        if (!is_null($user->sms_send_count)) {
            return $user->sms_send_count === $this->smsSendMaxCount;
        }

        return false;
    }


    /**
     * Проверка что можно отправлять SMS по таймлимиту
     *
     * @param User $user
     * @return int 0 - можно отправлять, int - кол-во секунд паузы
     */
    private function isRestrictedSendByTimelimit(User $user): int
    {
        if (!is_null($user->sms_repeat_at)) {
            $expired = Carbon::parse($user->sms_repeat_at);
            $diffSeconds = now()->diffInSeconds($expired, false);

            if ($diffSeconds < $this->smsSendPauseSeconds) {
                return $diffSeconds;
            }

        }

        return 0;
    }


    /**
     * Проверка длины кода
     *
     * @param string $sms_code
     * @return bool
     */
    private function checkCodeLenght(string $sms_code): bool
    {
        return strlen($sms_code) === $this->smsCodeLenght;
    }

    /**
     * Высылает код на номер телефона юзера
     *
     * @param User $user
     * @return bool|int
     * @throws \Exception
     */
    private function sendCodeToPhone(User $user): bool|int
    {

        $sms_code = random_int(1000, 9999);

        if ($this->sendSms($user->phone, 'Prodajka.com - код подтверждения: ' . $sms_code)) {

            $sendCount = $user->sms_send_count;
            if (is_null($sendCount)) {
                $sendCount = 0;
            }

            try {
                $user->sms_code = $sms_code;
                $user->sms_sended_at = now()->toDateTimeString();
                $user->sms_send_count = $sendCount + 1;
                $user->sms_confirm_retry = 0;
                $user->sms_repeat_at = now()->addSeconds($this->smsSendPauseSeconds)->toDateTimeString();
                $user->save();
            } catch (\Exception $exception) {
                Log::error('sendCodeToPhone save info to user DB: ' . $exception->getMessage());
                return false;
            }

            return (string)$sms_code;


        }

        return false;

    }

}
