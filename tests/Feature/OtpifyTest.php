<?php

namespace Tests\Feature;

use App\Enums\Area;
use Tests\TestCase;
use App\Models\User;
use Modules\Otpify\Models\OtpifyCode;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OtpifyTest extends TestCase
{
    use RefreshDatabase;

    private function login()
    {
        $email = 'a@a.aa';
        $passwordPlainText = '12345678';
        $passwordEncrypted = bcrypt('12345678');
        $source = 'admin';

        # create user
        User::factory()->create([
            'email' => $email,
            'password' => $passwordEncrypted
        ]);

        # login user
        $loginResponse = $this->postJson('api/auth/login', [
            'email' => $email,
            'password' => $passwordPlainText,
            'source' => $source
        ]);

        return $loginResponse->getOriginalContent()['token'];
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_otp_generated_for_empty_area(): void
    {
        $token = $this->login();

        # get auth user data
        $getOtpCodeResponse = $this->withToken($token)->postJson('api/generate-otp');
        $getOtpCodeResponse->assertStatus(422)->assertJson([
            "message" => "The area field is required.",
            "errors" => [
                "area" => [
                    "The area field is required."
                ]
            ]
        ]);

    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_otp_generated_for_invalid_area(): void
    {
        $token = $this->login();

        # get auth user data
        $getOtpCodeResponse = $this->withToken($token)->postJson('api/generate-otp', ['area' => 'test']);
        $getOtpCodeResponse->assertStatus(422)->assertJson([
            "message" => "The selected area is invalid.",
            "errors" => [
                "area" => [
                    "The selected area is invalid."
                ]
            ]
        ]);

    }

    // TODO test twilio configuration
    public function test_otp_generated_for_driver_fail()
    {
        $token = $this->login();

        # get auth user data
        $getOtpCodeResponse = $this->withToken($token)->postJson('api/generate-otp', ['area' => Area::Customer]);
        $getOtpCodeResponse->assertStatus(400)->assertJsonStructure([
            'message'
        ]);
    }

    public function test_otp_generated()
    {
        $token = $this->login();

        # get auth user data
        $getOtpCodeResponse = $this->withToken($token)->postJson('api/generate-otp', ['area' => Area::General]);
        $getOtpCodeResponse->assertStatus(200)->assertJsonStructure([
            'data'
        ]);
    }

    public function test_otp_verify()
    {
        $token = $this->login();

        # get auth user data
        $getOtpCodeResponse = $this->withToken($token)->postJson('api/generate-otp', ['area' => Area::General]);

        $vid = $getOtpCodeResponse->getOriginalContent()['data']['otp_code']->id;

        OtpifyCode::query()->where('id', $vid )->update(['otp_code' => bcrypt(1000)]);

        $getOtpCodeResponse = $this->withToken($token)->postJson('api/verify-otp', [
            'area' => Area::General,
            'vid' => $vid,
            'code' => 1000,
            ]);

        $getOtpCodeResponse->assertStatus(200)->assertJsonStructure([
            'data'
        ]);
    }

    public function test_otp_verify_for_invalid_otp_code()
    {
        $token = $this->login();

        # get auth user data
        $getOtpCodeResponse = $this->withToken($token)->postJson('api/generate-otp', ['area' => Area::General]);

        $vid = $getOtpCodeResponse->getOriginalContent()['data']['otp_code']->id;

        $getOtpCodeResponse = $this->withToken($token)->postJson('api/verify-otp', [
            'area' => Area::General,
            'vid' => $vid,
            'code' => 10005,
        ]);

        $getOtpCodeResponse->assertStatus(401)->assertJsonStructure([
            'message'
        ]);
    }
}
