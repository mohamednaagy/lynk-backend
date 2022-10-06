<?php

namespace Tests\Feature;

use App\Enums\Area;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_all_settings()
    {
        $token = $this->login();
        $response = $this->withToken($token)->getJson('api/v1/admin/settings');

        $response->assertStatus(200)->assertJsonStructure([
            'data',
        ]);
    }

    public function test_update_settings_with_empty_request()
    {
        $token = $this->login();
        $response = $this->withToken($token)->putJson('api/v1/admin/settings/update');

        $response->assertStatus(422)->assertJson([
            'message' => 'The area field is required.',
            'errors' => [
                'area' => [
                    'The area field is required.',
                ],
            ],
        ]
        );
    }

    public function test_update_settings_with_empty_area()
    {
        $token = $this->login();
        $response = $this->withToken($token)->putJson('api/v1/admin/settings/update', [
            'area' => '',
        ]);

        $response->assertStatus(422)->assertJson([
            'message' => 'The area field is required.',
            'errors' => [
                'area' => [
                    'The area field is required.',
                ],
            ],
        ]
        );
    }

    public function test_update_settings()
    {
        $token = $this->login();
        $data = [
            'area' => Area::General,
            'otp_driver' => 'twilio',
        ];

        $response = $this->withToken($token)->putJson('api/v1/admin/settings/update', $data);

        $response->assertStatus(200)->assertJsonStructure([
            'data',
        ]);
    }
}
