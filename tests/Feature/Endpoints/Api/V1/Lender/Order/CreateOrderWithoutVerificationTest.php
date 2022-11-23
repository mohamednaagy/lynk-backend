<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\Order;

use App\Enums\Role;
use App\Enums\WalletType;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateOrderWithoutVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_is_empty()
    {
        $campany = Company::factory()->create();
        $campany->createWallet(['name' => WalletType::CompanyWallet, 'slug' => WalletType::CompanyWallet]);

        $this->lenderLogin(Role::LenderAdmin, null, $campany);

        $data = [
            'national_id' => '2553451234',
            'amount' => 1000,
            'selling_price' => 1000.5,
            'phone_country_code' => 'SA',
            'phone_number' => '503811915',
        ];

        $response = $this->postJson('/api/v1/lender/orders/no-verification', $data, ['X-Company' => $campany->id]);
        $response->assertStatus(500);
    }

    public function test_national_id_is_not_valid()
    {
        $campany = Company::factory()->create();
        $this->lenderLogin(Role::LenderAdmin, null, $campany);
        $data = [
            'national_id' => '25513451234',
            'amount' => 1000,
            'selling_price' => 1000.5,
            'phone_country_code' => 'SA',
            'phone_number' => '503811915',
        ];

        $response = $this->postJson('/api/v1/lender/orders/no-verification', $data, ['X-Company' => $campany->id]);
        $response->assertStatus(422)->assertJsonFragment([
            'message' => 'The national ID must be 10 digits. (and 1 more error)',
            'errors' => [
                'national_id' => [
                    0 => 'The national ID must be 10 digits.',
                    1 => __('validation.national_id_wrong_format'),
                ],
            ],
        ]);
    }

    public function test_phone_number_is_not_valid()
    {
        $campany = Company::factory()->create();
        $this->lenderLogin(Role::LenderAdmin, null, $campany);
        $data = [
            'national_id' => '2553451234',
            'amount' => 1000,
            'selling_price' => 1000.5,
            'phone_country_code' => 'SA',
            'phone_number' => '01010420399',
        ];

        $response = $this->postJson('/api/v1/lender/orders/no-verification', $data, ['X-Company' => $campany->id]);
        $response->assertStatus(422)->assertJsonFragment([
            'message' => 'The phone number is not a valid phone number.',
            'errors' => [
                'phone_number' => [
                    0 => 'The phone number is not a valid phone number.',
                ],
            ],
        ]);
    }

    public function test_amount_can_not_be_more_than_selling_price()
    {
        $amount = 10;
        $sellingPrice = 9;
        $data = [
            'national_id' => '2553451234',
            'amount' => $amount,
            'selling_price' => $sellingPrice,
            'phone_country_code' => 'SA',
            'phone_number' => '503811915',
        ];

        $campany = Company::factory()->create();
        $this->lenderLogin(Role::LenderAdmin, null, $campany);

        $response = $this->postJson('/api/v1/lender/orders/no-verification', $data, ['X-Company' => $campany->id]);
        $response->assertStatus(422)->assertJsonFragment([
            'message' => 'The selling price must be greater than or equal to '.$amount.'.',
            'errors' => [
                'selling_price' => [
                    0 => 'The selling price must be greater than or equal to '.$amount.'.',
                ],
            ],
        ]);
    }

    public function test_order_created_successfully()
    {
        $campany = Company::factory()->create();
        $campany->createWallet(['balance' => 2000, 'name' => WalletType::CompanyWallet, 'slug' => WalletType::CompanyWallet]);

        $this->lenderLogin(Role::LenderAdmin, null, $campany);

        $data = [
            'national_id' => '2553451234',
            'amount' => 1000,
            'selling_price' => 1000.5,
            'phone_country_code' => 'SA',
            'phone_number' => '503811915',
        ];

        $response = $this->postJson('/api/v1/lender/orders/no-verification', $data, ['X-Company' => $campany->id]);
        $response->assertStatus(200);
    }
}
