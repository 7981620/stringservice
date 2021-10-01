<?php

namespace Agenta\StringService;

use Propaganistas\LaravelPhone\PhoneNumber;

class StringService
{

    protected SmsService $sms;
    protected TelegramService $telegram;

    public function __construct()
    {
        $this->sms = new SmsService();
        $this->telegram = new TelegramService();
    }

    /**
     * Экземпляр класса отправки sms
     *
     * @return SmsService
     */
    public function smsService(): SmsService
    {
        return $this->sms;
    }

    /**
     * Экземпляр класса отправки в Telegram
     *
     * @return TelegramService
     */
    public function telegramService(): TelegramService
    {
        return $this->telegram;
    }


    /**
     * Отображает украинский номер мобильного
     * в виде 38(000)00-00-00
     *
     * @param  string  $phone
     * @return string
     */
    public function phoneHuman(string $phone): string
    {

        $phone = $this->phoneUaTransform($phone);

        return '+'.sprintf("%s(%s)%s-%s-%s",
                substr($phone, 0, 2),
                substr($phone, 2, 3),
                substr($phone, 5, 3),
                substr($phone, 8, 2),
                substr($phone, 10)
            );
    }

    /**
     * Добавляет префикс к украинскому номеру телефона
     * если он начинается не с 380
     *
     * @param  string  $phone
     * @return string
     */
    public function phoneUaTransform(string $phone): string
    {

        if (!str_starts_with($phone, '380')) {
            if (str_starts_with($phone, '80')) {
                return '3'.$phone;
            }
            if (str_starts_with($phone, '0')) {
                return '38'.$phone;
            }

        }
        return '380'.$phone;
    }


    /**
     * Массив с кодами украинских операторов мобильной связи
     * в формате 0XX
     *
     * @return array
     */
    public function phonesMobileUaCodes(): array
    {
        return explode(',', config('stringservice.phone_ua_codes'));
    }


    /**
     * Переводит копейки в гривну и отображает в формате 0,00
     *
     * @param  int  $value
     * @return string
     */
    public function showInUah(int $value): string
    {
        $value /= 100;
        return number_format($value, 2, ',', ' ');
    }

    /**
     * Форматирует дробное (или целое) число для отображения с двумя знаками после запятой
     *
     * @param $value
     * @return string
     */
    public function showNumber($value): string
    {
        return number_format($value, 2, ',', ' ');
    }

    /**
     * Форматирует целое число для отображения без знаков после запятой
     *
     * @param $value
     * @return string
     */
    public function showInteger($value): string
    {
        return number_format((int) $value, 0, '', ' ');
    }

    /**
     * Переводит число в копейки и тип int
     *
     * @param  string  $number
     * @return int
     */
    public function toCoins(string $number): int
    {
        $bcmathInteger = bcmul(self::floatValue($number), 100, 0);
        return (integer) $bcmathInteger;
    }

    /**
     * Конвертирует строку с разделителем ',' в float
     * с округлением до двух знаков
     *
     * @param $val
     * @return float
     */
    public function floatValue($val): float
    {
        $val = trim($val);
        $val = str_replace(',', '.', $val);
        $val = preg_replace('/\.(?=.*\.)/', '', $val);
        $val = (float) $val;
        return round((float) $val, 2);
    }


    /**
     * Типограф текста
     *
     * @param  string  $text
     * @return string
     */
    public function typograph(string $text): string
    {

        $text = html_entity_decode($text, ENT_QUOTES, 'utf-8');
        $arr = array(
            // Убираем символ троеточия
            '/…/u' => '...',
            // Кавычки «ёлочки» &laquo; &raquo;
            '/(^|[\s;\(\[-])"/' => '$1«',
//            '/"([\s-\.!,:;\?\)\]\n\r]|$)/' => '»$1',
            '/"([\s\.!,:;\?\)\]\n\r]|$)/' => '»$1',
            '/([^\s])"([^\s])/' => '$1»$2',
//            // Длинное тире &mdash;
            '/(^|\n|["„«])--?(\s)/u' => '$1—$2',
            '/(\s)--?(\s)/' => ' —$2',
//            // Непереносимый проблел после коротких слов &nbsp;
            '/([\s][a-zа-яё]{1,2})[ ]/iu' => '$1 '
        );

        foreach ($arr as $key => $val) {
            $text = preg_replace($key, $val, $text);
        }


        // Вложенные кавычки &bdquo; &ldquo;
        while (preg_match('/(«[^«»]*)«/mu', $text)) {
            $text = preg_replace('/(«[^«»]*)«/mu', '$1„', $text);
            $text = preg_replace('/(„[^„“«»]*)»/mu', '$1“', $text);
        }

        return (string) $text;

    }

    /**
     * Парсинг телефона из строки
     *
     * @param  string  $string
     * @return array|string|string[]|null
     */
    public function getMobilePhone(string $string)
    {

        $string = str_replace(',', ';', $string);
        $array = explode(';', $string);

        foreach ($array as $phone) {

            $phone = preg_replace('/[^0-9]/', '', $phone);
            try {
                $phoneNumber = PhoneNumber::make($phone, 'UA');
            } catch (\Exception $exception) {
                continue;
            }

            try {
                $isMobile = $phoneNumber->isOfType('mobile');
            } catch (\Exception $exception) {
                continue;
            }

            if ($isMobile) {
                return str_replace('+', '', $phoneNumber->formatE164());
            }
        }

        return null;

    }


    /**
     * Делает из номера банковской карты маску
     *
     * @param  string  $card
     * @return array|string|string[]
     */
    public function maskBankCard(string $card)
    {
        return substr_replace($card, str_repeat("*", 6), 6, 6);
    }

    /**
     * Генерация пароля с указанной длиной
     *
     * @param  int  $passwordLength  длина пароля
     * @return bool|string
     */
    public function passwordGenerator(int $passwordLength = 8)
    {
        $random = str_shuffle('abcdefghjklmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ234567890_-#@');
        return substr($random, 0, $passwordLength);
    }


    /**
     * Генерирует случайную строку заданной длины
     *
     * @param  int  $length  длина строки
     * @return string
     * @throws \Exception
     */
    public function randomStringFromBytes(int $length = 8): string
    {
        return (string) str_shuffle(substr(bin2hex(random_bytes($length)), 0, $length));
    }

    /**
     * Генерирует случайный набор символов согласно переданному
     *
     * @param  int  $strength  длина/стойкость
     * @param  string  $input  набор символов
     * @return string
     * @throws \Exception
     */
    public function randomAlphaNumString(
        int $strength = 16,
        string $input = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
    ): string {
        $input_length = strlen($input);
        $random_string = '';
        for ($i = 0; $i < $strength; $i++) {
            $random_character = $input[random_int(0, $input_length - 1)];
            $random_string .= $random_character;
        }
        return (string) str_shuffle($random_string);
    }


}
