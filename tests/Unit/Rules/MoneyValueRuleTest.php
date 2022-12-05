<?php

namespace Tests\Unit\Rules;

use App\Rules\MoneyValueRule;
use Tests\TestCase;

class MoneyValueRuleTest extends TestCase
{
    protected MoneyValueRule $rule;

    protected string $attribute;

    public function setUp(): void
    {
        parent::setUp();

        $this->rule = new MoneyValueRule();
        $this->attribute = 'amount';
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function test_passes_return_bool()
    {
        $this->assertIsBool($this->rule->passes($this->attribute, 0));
    }

    public function test_passes_return_false_when_value_is_zero()
    {
        $this->assertFalse($this->rule->passes($this->attribute, 0));
    }

    public function test_passes_return_false_when_value_is_negative()
    {
        $this->assertFalse($this->rule->passes($this->attribute, -50));
    }

    public function test_passes_return_true_when_value_is_postive()
    {
        $this->assertTrue($this->rule->passes($this->attribute, 1));
    }

    public function test_passes_return_true_when_value_is_onley_decimal()
    {
        $this->assertTrue($this->rule->passes($this->attribute, .20));
    }

    public function test_passes_return_true_when_value_is_zero_with_decimal()
    {
        $this->assertTrue($this->rule->passes($this->attribute, 0.20));
    }

    public function test_passes_return_true_when_value_is_big_number_postive()
    {
        $this->assertTrue($this->rule->passes($this->attribute, 9999999));
    }

    public function test_passes_return_true_when_value_is_decimal()
    {
        $this->assertTrue($this->rule->passes($this->attribute, 101.50));
    }

    public function test_passes_return_true_when_value_is_big_number_with_decimal()
    {
        $this->assertTrue($this->rule->passes($this->attribute, 9999999.50));
    }

    public function test_passes_return_false_when_more_than_two_decimal()
    {
        $this->assertFalse($this->rule->passes($this->attribute, 101.501));
    }

    public function test_decimal_could_customize()
    {
        $rule = new MoneyValueRule(4);

        $this->assertTrue($rule->passes('amount', 999.9999));
    }

    public function test_english_message()
    {
        $this->app->setLocale('en');

        $this->rule->passes($this->attribute, 0);

        $this->assertSame($this->rule->message(), 'The :attribute format is invalid.');
    }

    public function test_arabic_message()
    {
        $this->app->setLocale('ar');

        $this->rule->passes($this->attribute, 0);

        $this->assertSame($this->rule->message(), 'صيغة حقل :attribute .غير صحيحة.');
    }
}
