<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function login()
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

        $loginResponse->assertStatus(200)->assertJsonStructure([
            "token"
        ]);

        return $loginResponse->getOriginalContent()['token'];
    }

}
