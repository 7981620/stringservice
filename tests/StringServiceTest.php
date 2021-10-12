<?php

namespace Agenta\StringService\Test;

use Agenta\StringService\MyPackageServiceProvider;
use Agenta\StringService\StringService;
use Orchestra\Testbench\TestCase;

class StringServiceTest extends TestCase
{
    protected $stringService;

    protected function getPackageProviders($app)
    {
        return [
            MyPackageServiceProvider::class
        ];
    }

    public function __construct()
    {
        parent::__construct();
        $this->stringService = new StringService();
    }

    public function test_maskBankCard(): void
    {
        $testCard = '4111111111112345';
        $mask = '411111******2345';
        $result = $this->stringService->maskBankCard($testCard);
        $this->assertEquals($mask, $result);
    }

    public function test_phoneHuman(): void
    {
        $phone = '380985552211';
        $expect = '+38(098)555-22-11';
        $result = $this->stringService->phoneHuman($phone);
        $this->assertEquals($expect, $result);

    }

    public function test_phonesMobileUaCodes()
    {
        $this->assertIsArray($this->stringService->phonesMobileUaCodes());
    }

    public function test_showInUah()
    {
        $amount = 100000;
        $expected = '1 000,00';
        $result = $this->stringService->showInUah($amount);
        $this->assertEquals($expected, $result);
    }

    /**
     * целое число
     */
    public function test_showNumber_int(): void
    {
        $number = '1000000';
        $expected = '1 000 000,00';
        $result = $this->stringService->showNumber($number);
        $this->assertEquals($expected, $result);
    }

    /**
     * дробное число с двумя знаками
     */
    public function test_showNumber_float(): void
    {
        $number = '1000000.99';
        $expected = '1 000 000,99';
        $result = $this->stringService->showNumber($number);
        $this->assertEquals($expected, $result);
    }

    /**
     * округление в большую сторону
     */
    public function test_showNumber_float_three_decimal_round_up(): void
    {
        $number = '1000000.996';
        $expected = '1 000 001,00';
        $result = $this->stringService->showNumber($number);
        $this->assertEquals($expected, $result);
    }

    /**
     * округление в меньшую сторону
     */
    public function test_showNumber_float_three_decimal_round_down(): void
    {
        $number = '1000000.994';
        $expected = '1 000 000,99';
        $result = $this->stringService->showNumber($number);
        $this->assertEquals($expected, $result);
    }

    /**
     * банковское округление пятерки в большую сторону
     */
    public function test_showNumber_float_three_decimal_round_bank(): void
    {
        $number = '1000000.995';
        $expected = '1 000 001,00';
        $result = $this->stringService->showNumber($number);
        $this->assertEquals($expected, $result);
    }

    public function test_showInteger()
    {
        $number = 1000000;
        $expected = '1 000 000';
        $result = $this->stringService->showInteger($number);
        $this->assertEquals($expected, $result);
    }

    /**
     * если передано дробное число
     */
    public function test_showInteger_if_float(): void
    {
        $number = 1000000.55;
        $expected = '1 000 000';
        $result = $this->stringService->showInteger($number);
        $this->assertEquals($expected, $result);
    }

    public function test_toCoins(): void
    {
        $number = 100.99;
        $expected = '10099';
        $result = $this->stringService->toCoins($number);
        $this->assertEquals($expected, $result);
    }

    public function test_toCoins_round_up(): void
    {
        $number = 100.996;
        $expected = '10100';
        $result = $this->stringService->toCoins($number);
        $this->assertEquals($expected, $result);
    }

    public function test_toCoins_round_down(): void
    {
        $number = 100.994;
        $expected = '10099';
        $result = $this->stringService->toCoins($number);
        $this->assertEquals($expected, $result);
    }

    public function test_toCoins_round_bank(): void
    {
        $number = 100.995;
        $expected = '10100';
        $result = $this->stringService->toCoins($number);
        $this->assertEquals($expected, $result);
    }

    public function test_floatValue(): void
    {
        $number = '100,50';
        $expected = 100.50;
        $result = $this->stringService->floatValue($number);
        $this->assertIsFloat($result);
        $this->assertEquals($expected, $result);
    }

    public function test_floatValue_round_up(): void
    {
        $number = '100,506';
        $expected = 100.51;
        $result = $this->stringService->floatValue($number);
        $this->assertIsFloat($result);
        $this->assertEquals($expected, $result);
    }

    public function test_floatValue_round_bank(): void
    {
        $number = '100,505';
        $expected = 100.51;
        $result = $this->stringService->floatValue($number);
        $this->assertIsFloat($result);
        $this->assertEquals($expected, $result);
    }

    public function test_floatValue_round_down(): void
    {
        $number = '100,504';
        $expected = 100.50;
        $result = $this->stringService->floatValue($number);
        $this->assertIsFloat($result);
        $this->assertEquals($expected, $result);
    }


    public function test_getMobilePhone_sep_coma()
    {
        $string = '+38(050) 333-22 11,0984440099,0502001010|380673332211/380939993020';
        $expected = '380503332211';
        $result = $this->stringService->getMobilePhone($string);
        $this->assertEquals($expected, $result);
    }

    public function test_passwordGenerator()
    {
        $lenght = 10;
        $result = $this->stringService->passwordGenerator($lenght);
        $this->assertIsString($result);
        $this->assertEquals($lenght, strlen($result));
    }

    public function test_randomStringFromBytes()
    {
        $lenght = 16;
        $result = $this->stringService->randomStringFromBytes($lenght);
        $this->assertIsString($result);
        $this->assertEquals($lenght, strlen($result));
    }

    public function test_randomStringFromBytes_if_lenght_zero()
    {
        $result = $this->stringService->randomStringFromBytes(0);
        $this->assertIsString($result);
        $this->assertEquals(1, strlen($result));
    }


    public function test_randomAlphaNumString() {
        $lenght = 16;
        $result = $this->stringService->randomAlphaNumString();
        $this->assertIsString($result);
        $this->assertEquals($lenght, strlen($result));
    }

    public function test_randomAlphaNumString_with_lenght() {
        $lenght = 10;
        $result = $this->stringService->randomAlphaNumString($lenght);
        $this->assertIsString($result);
        $this->assertEquals($lenght, strlen($result));
    }

    public function test_randomAlphaNumString_with_lenght_and_chars() {
        $lenght = 10;
        $result = $this->stringService->randomAlphaNumString($lenght, 'A');
        $this->assertIsString($result);
        $this->assertEquals($lenght, strlen($result));
        $this->assertEquals('AAAAAAAAAA', $result);
    }



}
