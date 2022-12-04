<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\FinancingOrders;

use App\Enums\Role;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class OrdersVolumeTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithLender;

    private static Company $company;

    private static Company $secondCompany;

    private static User $userLenderAdmin;

    private static User $secondUserLenderAdmin;

    private static Wallet $wallet;

    private static Wallet $secondWallet;

    private static Builder|Model $order;

    private static Builder|Model $secondorder;

    private static string  $firstDateForFirstCompany;

    private static string  $secondDateForFirstCompany;

    private static $ordersCountForFirstCompany;

    private static $ordersCountForSecondDateInFrstCompany;

    private static string  $firstDateForSecondCompany;

    private static string  $secondDateForSecondCompany;

    private static $ordersCountForSecondDateSecondCompany;

    private static $ordersCountForSecondCompany;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        self::$firstDateForFirstCompany = date('2021-10-01');
        self::$secondDateForFirstCompany = date('2023-10-01');
        self::$ordersCountForSecondDateInFrstCompany = 3;
        self::$ordersCountForFirstCompany = 5;

        [self::$company, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'lenderAdmin@bim.com');

        FinancingOrder::factory(self::$ordersCountForFirstCompany)->create([
            'company_id' => self::$company->id,
            'creator_id' => self::$userLenderAdmin->id,
            'creator_type' => self::$userLenderAdmin->getMorphClass(),
            'created_at' => self::$firstDateForFirstCompany,
        ]);

        FinancingOrder::factory(self::$ordersCountForSecondDateInFrstCompany)->create([
            'company_id' => self::$company->id,
            'creator_id' => self::$userLenderAdmin->id,
            'creator_type' => self::$userLenderAdmin->getMorphClass(),
            'created_at' => self::$secondDateForFirstCompany,
        ]);

        // the second company to simplify weeks response

        [self::$secondCompany, self::$secondWallet] = $this->createCompany('2000', ['company_cr' => '12345678810']);
        self::$secondUserLenderAdmin = $this->createLenderUser(self::$secondCompany->id, Role::LenderAdmin, 'secondLenderAdmin@bim.com');

        self::$firstDateForSecondCompany = date('2022-08-01');
        self::$secondDateForSecondCompany = date('2022-10-01');
        self::$ordersCountForSecondCompany = 5;
        self::$ordersCountForSecondDateSecondCompany = 6;

        FinancingOrder::factory(self::$ordersCountForSecondDateSecondCompany)->create([
            'company_id' => self::$secondCompany->id,
            'creator_id' => self::$userLenderAdmin->id,
            'creator_type' => self::$userLenderAdmin->getMorphClass(),
            'created_at' => self::$firstDateForSecondCompany,
        ]);

        FinancingOrder::factory(self::$ordersCountForSecondCompany)->create([
            'company_id' => self::$secondCompany->id,
            'creator_id' => self::$userLenderAdmin->id,
            'creator_type' => self::$userLenderAdmin->getMorphClass(),
            'created_at' => self::$secondDateForSecondCompany,
        ]);
    }

    public function test_get_orders_volume_with_default_period()
    {
        $this->actingAs(self::$userLenderAdmin)->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/orders/volume', ['X-Company' => self::$company->id])
            ->assertStatus(200)->assertJsonFragment([
                'y_axis' => [
                    0 => 5,
                    1 => 0,
                    2 => 3,
                ],
                'x_axis' => [
                    0 => 2021,
                    1 => 2022,
                    2 => 2023,
                ],
            ]);
    }

    public function test_get_orders_volume_with_month_period()
    {
        $query = http_build_query([
            'period' => 'month',
        ]);

        $this->actingAs(self::$userLenderAdmin)
            ->getJson(
                '/api/v1/lender/orders/volume?'.$query,
                ['X-Company' => self::$company->id]
            )
            ->assertStatus(200)->assertJsonFragment([
                'y_axis' => [
                    0 => 5,
                    1 => 0,
                    2 => 0,
                    3 => 0,
                    4 => 0,
                    5 => 0,
                    6 => 0,
                    7 => 0,
                    8 => 0,
                    9 => 0,
                    10 => 0,
                    11 => 0,
                    12 => 0,
                    13 => 0,
                    14 => 0,
                    15 => 0,
                    16 => 0,
                    17 => 0,
                    18 => 0,
                    19 => 0,
                    20 => 0,
                    21 => 0,
                    22 => 0,
                    23 => 0,
                    24 => 3,
                ],
                'x_axis' => [
                    0 => '2021-10',
                    1 => '2021-11',
                    2 => '2021-12',
                    3 => '2022-01',
                    4 => '2022-02',
                    5 => '2022-03',
                    6 => '2022-04',
                    7 => '2022-05',
                    8 => '2022-06',
                    9 => '2022-07',
                    10 => '2022-08',
                    11 => '2022-09',
                    12 => '2022-10',
                    13 => '2022-11',
                    14 => '2022-12',
                    15 => '2023-01',
                    16 => '2023-02',
                    17 => '2023-03',
                    18 => '2023-04',
                    19 => '2023-05',
                    20 => '2023-06',
                    21 => '2023-07',
                    22 => '2023-08',
                    23 => '2023-09',
                    24 => '2023-10',
                ],
            ]);
    }

    public function test_get_orders_volume_with_week_period()
    {
        $query = http_build_query([
            'starting_date' => '2022-01-01',
            'ending_date' => '2022-10-31',
            'period' => 'week',
        ]);

        $this->actingAs(self::$secondUserLenderAdmin)
            ->getJson(
                '/api/v1/lender/orders/volume?'.$query,
                ['X-Company' => self::$secondCompany->id]
            )
            ->assertStatus(200)->assertJsonFragment([
                'y_axis' => [
                    0 => 6,
                    1 => 0,
                    2 => 0,
                    3 => 0,
                    4 => 0,
                    5 => 0,
                    6 => 0,
                    7 => 0,
                    8 => 0,
                    9 => 0,
                    10 => 5,
                ],
                'x_axis' => [
                    0 => '2022-08 (week 31)',
                    1 => '2022-08 (week 32)',
                    2 => '2022-08 (week 33)',
                    3 => '2022-08 (week 34)',
                    4 => '2022-08 (week 35)',
                    5 => '2022-09 (week 35)',
                    6 => '2022-09 (week 36)',
                    7 => '2022-09 (week 37)',
                    8 => '2022-09 (week 38)',
                    9 => '2022-09 (week 39)',
                    10 => '2022-10 (week 39)',
                ],
            ]);
    }

    public function test_starting_date_cannot_be_after_ending_date()
    {
        $query = http_build_query([
            'starting_date' => '2022-01-01',
            'ending_date' => '2020-10-31',
            'period' => 'week',
        ]);

        $this->actingAs(self::$secondUserLenderAdmin)
            ->getJson(
                '/api/v1/lender/orders/volume?'.$query,
                ['X-Company' => self::$secondCompany->id]
            )->assertStatus(422)->assertJsonFragment([
                'message' => 'The starting date must be a date before ending date.',
                'errors' => [
                    'starting_date' => [
                        0 => 'The starting date must be a date before ending date.',
                    ],
                ],
            ]);
    }

    public function test_wrong_date_fromat()
    {
        $query = http_build_query([
            'starting_date' => '2022-01',
            'ending_date' => '2020',
            'period' => 'week',
        ]);

        $this->actingAs(self::$secondUserLenderAdmin)
            ->getJson(
                '/api/v1/lender/orders/volume?'.$query,
                ['X-Company' => self::$secondCompany->id]
            )
            ->assertStatus(422)->assertJsonFragment([
                'message' => 'The starting date does not match the format Y-m-d. (and 1 more error)',
                'errors' => [
                    'starting_date' => [
                        0 => 'The starting date does not match the format Y-m-d.',
                    ],
                    'ending_date' => [
                        0 => 'The ending date does not match the format Y-m-d.',
                    ],
                ],
            ]);
    }
}
