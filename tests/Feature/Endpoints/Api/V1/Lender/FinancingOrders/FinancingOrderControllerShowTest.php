<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\FinancingOrders;

use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use Bavix\Wallet\Models\Wallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class FinancingOrderControllerShowTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    private static Company $firstCompany;

    private static Company $secondCompany;

    private static User $userLenderAdmin;

    private static User $userLenderSupervisor;

    private static User $userLenderBilling;

    private static User $userLenderOrderCreator;

    private static Wallet $firstWallet;

    private static Wallet $secondWallet;

    private static Builder|Model $firstOrderInSameCompany;

    private static Builder|Model $secondOrderInSameCompany;

    private static Builder|Model $thirdOrderInSameCompany;

    private static Builder|Model $firstOrderInOtherCompany;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$firstCompany, self::$firstWallet] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        [self::$secondCompany, self::$secondWallet] = $this->createCompany('3000', ['company_cr' => '12345678911']);
        self::$userLenderAdmin = $this->createLenderUser(self::$firstCompany->getOriginal('id'), Role::LenderAdmin, 'lenderAdmin@bim.com');
        self::$userLenderSupervisor = $this->createLenderUser(self::$firstCompany->getOriginal('id'), Role::LenderSupervisor, 'lenderSupervisor@bim.com');
        self::$userLenderBilling = $this->createLenderUser(self::$firstCompany->getOriginal('id'), Role::LenderBilling, 'lenderBilling@bim.com');
        self::$userLenderOrderCreator = $this->createLenderUser(self::$firstCompany->getOriginal('id'), Role::LenderOrderCreator, 'lenderOrderCreator@bim.com');
        self::$firstOrderInSameCompany = $this->createOrder(self::$firstCompany->getOriginal('id'), self::$userLenderAdmin->getOriginal('id'));
        self::$secondOrderInSameCompany = $this->createOrder(self::$firstCompany->getOriginal('id'), self::$userLenderAdmin->getOriginal('id'));
        self::$thirdOrderInSameCompany = $this->createOrder(self::$firstCompany->getOriginal('id'), self::$userLenderOrderCreator->getOriginal('id'));
        self::$firstOrderInOtherCompany = $this->createOrder(self::$secondCompany->getOriginal('id'), self::$userLenderAdmin->getOriginal('id'));
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_show_order(): void
    {
        $this->withHeader('X-Company', self::$firstCompany->getOriginal('id'))
            ->getJson('api/v1/lender/orders/'.self::$firstOrderInSameCompany->getOriginal('id'))
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_user_can_show_order_in_same_company(): void
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$firstCompany->getOriginal('id'))
            ->getJson('api/v1/lender/orders/'.self::$firstOrderInSameCompany->getOriginal('id'))
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson([
                'data' => [
                    'phone_country_code' => self::$firstOrderInSameCompany->phoneNumberCountryCode,
                    'phone_number' => self::$firstOrderInSameCompany->mobileDialingPhoneNumber,
                    'phone_number_formatted' => self::$firstOrderInSameCompany->phone_number->formatInternational(),
                    'id' => self::$firstOrderInSameCompany->getOriginal('id'),
                    'status' => [
                        'description' => self::$firstOrderInSameCompany->status->description,
                        'value' => self::$firstOrderInSameCompany->status->value,
                    ],
                    'company_id' => self::$firstCompany->getOriginal('id'),
                    'reference_number' => self::$firstOrderInSameCompany->reference_number,
                    'national_id' => (int) self::$firstOrderInSameCompany->national_id,
                    'amount' => self::$firstOrderInSameCompany->amount,
                    'selling_price' => self::$firstOrderInSameCompany->selling_price,
                    'contract' => '',
                    'power_of_attorney' => '',
                    'is_approved' => self::$firstOrderInSameCompany->approved_at !== null,
                    'status_reason' => self::$firstOrderInSameCompany->status_reason,
                    'creator' => [
                        'id' => self::$userLenderAdmin->getOriginal('id'),
                        'name' => self::$userLenderAdmin->getOriginal('first_name').' '.self::$userLenderAdmin->getOriginal('last_name'),
                    ],
                    'approver' => null,
                    'history' => [
                        [
                            'step' => 'client_wakala',
                            'is_complete' => false,
                            'completed_at' => null,
                            'document' => null,
                        ],
                        [
                            'step' => 'commodity_purchased',
                            'is_complete' => false,
                            'completed_at' => null,
                            'cert_document' => [
                                'url' => null,
                                'date' => null,
                            ],
                            'ownership_document' => [
                                'url' => null,
                                'date' => null,
                            ],
                        ],
                        [
                            'step' => 'contract_singed',
                            'is_complete' => false,
                            'completed_at' => null,
                        ],
                        [
                            'step' => 'selling_commodity_to_customer',
                            'is_complete' => false,
                            'completed_at' => null,
                            'document' => null,
                        ],
                        [
                            'step' => 'selling_commodity_to_open_market',
                            'is_complete' => false,
                            'completed_at' => null,
                            'mpo_document' => [
                                'url' => null,
                                'date' => null,
                            ],
                            'warranty_document' => [
                                'url' => null,
                                'date' => null,
                            ],
                        ],
                        [
                            'step' => 'murabha_sale_completed',
                            'is_complete' => false,
                            'completed_at' => null,
                        ],
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_supervisor_user_can_show_order_in_same_company(): void
    {
        $this->actingAs(self::$userLenderSupervisor)
            ->withHeader('X-Company', self::$firstCompany->getOriginal('id'))
            ->getJson('api/v1/lender/orders/'.self::$firstOrderInSameCompany->getOriginal('id'))
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson([
                'data' => [
                    'phone_country_code' => self::$firstOrderInSameCompany->phoneNumberCountryCode,
                    'phone_number' => self::$firstOrderInSameCompany->mobileDialingPhoneNumber,
                    'phone_number_formatted' => self::$firstOrderInSameCompany->phone_number->formatInternational(),
                    'id' => self::$firstOrderInSameCompany->getOriginal('id'),
                    'status' => [
                        'description' => self::$firstOrderInSameCompany->status->description,
                        'value' => self::$firstOrderInSameCompany->status->value,
                    ],
                    'company_id' => self::$firstCompany->getOriginal('id'),
                    'reference_number' => self::$firstOrderInSameCompany->reference_number,
                    'national_id' => (int) self::$firstOrderInSameCompany->national_id,
                    'amount' => self::$firstOrderInSameCompany->amount,
                    'selling_price' => self::$firstOrderInSameCompany->selling_price,
                    'contract' => '',
                    'power_of_attorney' => '',
                    'is_approved' => self::$firstOrderInSameCompany->approved_at !== null,
                    'status_reason' => self::$firstOrderInSameCompany->status_reason,
                    'creator' => [
                        'id' => self::$userLenderAdmin->getOriginal('id'),
                        'name' => self::$userLenderAdmin->getOriginal('first_name').' '.self::$userLenderAdmin->getOriginal('last_name'),
                    ],
                    'approver' => null,
                    'history' => [
                        [
                            'step' => 'client_wakala',
                            'is_complete' => false,
                            'completed_at' => null,
                            'document' => null,
                        ],
                        [
                            'step' => 'commodity_purchased',
                            'is_complete' => false,
                            'completed_at' => null,
                            'cert_document' => [
                                'url' => null,
                                'date' => null,
                            ],
                            'ownership_document' => [
                                'url' => null,
                                'date' => null,
                            ],
                        ],
                        [
                            'step' => 'contract_singed',
                            'is_complete' => false,
                            'completed_at' => null,
                        ],
                        [
                            'step' => 'selling_commodity_to_customer',
                            'is_complete' => false,
                            'completed_at' => null,
                            'document' => null,
                        ],
                        [
                            'step' => 'selling_commodity_to_open_market',
                            'is_complete' => false,
                            'completed_at' => null,
                            'mpo_document' => [
                                'url' => null,
                                'date' => null,
                            ],
                            'warranty_document' => [
                                'url' => null,
                                'date' => null,
                            ],
                        ],
                        [
                            'step' => 'murabha_sale_completed',
                            'is_complete' => false,
                            'completed_at' => null,
                        ],
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_billing_user_cant_show_order_in_same_company(): void
    {
        $this->actingAs(self::$userLenderBilling)
            ->withHeader('X-Company', self::$firstCompany->getOriginal('id'))
            ->getJson('api/v1/lender/orders/'.self::$firstOrderInSameCompany->getOriginal('id'))
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @return void
     */
    public function test_that_order_creator_user_cant_show_order_not_owned_in_same_company(): void
    {
        $this->actingAs(self::$userLenderOrderCreator)
            ->withHeader('X-Company', self::$firstCompany->getOriginal('id'))
            ->getJson('api/v1/lender/orders/'.self::$firstOrderInSameCompany->getOriginal('id'))
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @return void
     */
    public function test_that_order_creator_user_can_show_order_owned_in_same_company(): void
    {
        $this->actingAs(self::$userLenderOrderCreator)
            ->withHeader('X-Company', self::$firstCompany->getOriginal('id'))
            ->getJson('api/v1/lender/orders/'.self::$thirdOrderInSameCompany->getOriginal('id'))
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson([
                'data' => [
                    'phone_country_code' => self::$thirdOrderInSameCompany->phoneNumberCountryCode,
                    'phone_number' => self::$thirdOrderInSameCompany->mobileDialingPhoneNumber,
                    'phone_number_formatted' => self::$thirdOrderInSameCompany->phone_number->formatInternational(),
                    'id' => self::$thirdOrderInSameCompany->getOriginal('id'),
                    'status' => [
                        'description' => self::$thirdOrderInSameCompany->status->description,
                        'value' => self::$thirdOrderInSameCompany->status->value,
                    ],
                    'company_id' => self::$firstCompany->getOriginal('id'),
                    'reference_number' => self::$thirdOrderInSameCompany->reference_number,
                    'national_id' => (int) self::$thirdOrderInSameCompany->national_id,
                    'amount' => self::$thirdOrderInSameCompany->amount,
                    'selling_price' => self::$thirdOrderInSameCompany->selling_price,
                    'contract' => '',
                    'power_of_attorney' => '',
                    'is_approved' => self::$thirdOrderInSameCompany->approved_at !== null,
                    'status_reason' => self::$thirdOrderInSameCompany->status_reason,
                    'creator' => [
                        'id' => self::$userLenderOrderCreator->getOriginal('id'),
                        'name' => self::$userLenderOrderCreator->getOriginal('first_name').' '.self::$userLenderAdmin->getOriginal('last_name'),
                    ],
                    'approver' => null,
                    'history' => [
                        [
                            'step' => 'client_wakala',
                            'is_complete' => false,
                            'completed_at' => null,
                            'document' => null,
                        ],
                        [
                            'step' => 'commodity_purchased',
                            'is_complete' => false,
                            'completed_at' => null,
                            'cert_document' => [
                                'url' => null,
                                'date' => null,
                            ],
                            'ownership_document' => [
                                'url' => null,
                                'date' => null,
                            ],
                        ],
                        [
                            'step' => 'contract_singed',
                            'is_complete' => false,
                            'completed_at' => null,
                        ],
                        [
                            'step' => 'selling_commodity_to_customer',
                            'is_complete' => false,
                            'completed_at' => null,
                            'document' => null,
                        ],
                        [
                            'step' => 'selling_commodity_to_open_market',
                            'is_complete' => false,
                            'completed_at' => null,
                            'mpo_document' => [
                                'url' => null,
                                'date' => null,
                            ],
                            'warranty_document' => [
                                'url' => null,
                                'date' => null,
                            ],
                        ],
                        [
                            'step' => 'murabha_sale_completed',
                            'is_complete' => false,
                            'completed_at' => null,
                        ],
                    ],
                ],
            ]);
    }
}
