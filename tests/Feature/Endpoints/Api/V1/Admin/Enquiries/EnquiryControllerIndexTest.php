<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Enquiries;

use App\Actions\Contracts\Enquiries\GetPaginatedEnquiries;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\EnquiryStatus;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\Enquiry;
use App\Models\User;
use App\Transformers\EnquiryTransformer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithAdmin;
use Tests\Traits\InteractsWithEnquiry;
use Tests\Traits\InteractsWithLender;

class EnquiryControllerIndexTest extends TestCase
{
    use RefreshDatabase, InteractsWithAdmin, InteractsWithLender, InteractsWithEnquiry;

    const BaseUrl = 'api/v1/admin/enquiries';

    private static Company $company;

    private static User $userLenderAdmin;

    private static User $admin;

    private static User $manager;

    private static User $managerHasPermission;

    private static Enquiry $lenderEnquiry;

    private static Enquiry $visitorEnquiry;

    private static mixed $paginatedEnquiries;

    /**
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$admin = $this->createAdmin();
        self::$manager = $this->createManager();
        self::$managerHasPermission = $this->createManager(
            'managerHasPermission@bim.com',
            perm(Area::SuperAdmin, [Subject::Enquiries, Action::Index])
        );
        [self::$company] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'lenderAdmin@bim.com');
        self::$lenderEnquiry = $this->createEnquiry(self::$userLenderAdmin);
        self::$visitorEnquiry = $this->createEnquiry(enquiryStatus: 2);
        self::$paginatedEnquiries = $this->app->make(GetPaginatedEnquiries::class);
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_index_enquiries(): void
    {
        $this->getJson(self::BaseUrl)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_user_has_admin_role_can_index_enquiries(): void
    {
        $this->actingAs(self::$admin)
            ->getJson(self::BaseUrl)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(self::$paginatedEnquiries->handle(), new EnquiryTransformer())
                    ->parseIncludes([
                        'id',
                        'subject',
                        'status',
                        'creation_date',
                        'creator',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    /**
     * @return void
     */
    public function test_that_auth_user_has_manager_role_and_right_permission_can_index_enquiries(): void
    {
        $this->actingAs(self::$managerHasPermission)
            ->getJson(self::BaseUrl)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(self::$paginatedEnquiries->handle(), new EnquiryTransformer())
                    ->parseIncludes([
                        'id',
                        'subject',
                        'status',
                        'creation_date',
                        'creator',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    /**
     * @return void
     */
    public function test_that_auth_user_has_manager_role_cannot_index_enquiries_with_no_permission(): void
    {
        $this->actingAs(self::$manager)
            ->getJson(self::BaseUrl)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', 'User does not have the right permissions.');
    }

    /**
     * @return void
     */
    public function test_index_filtered_enquiries_by_status_succeed(): void
    {
        $response = $this->actingAs(self::$admin)
            ->getJson(self::BaseUrl.'?status='.EnquiryStatus::UnderReview)
            ->assertStatus(Response::HTTP_OK);

        $this->assertEquals(EnquiryStatus::UnderReview, $response['data'][0]['status']['value']);
    }

    /**
     * @return void
     */
    public function test_index_filtered_enquiries_by_creator_succeed(): void
    {
        $response = $this->actingAs(self::$admin)
            ->getJson(self::BaseUrl.'?creator='.self::$userLenderAdmin->first_name)
            ->assertStatus(Response::HTTP_OK);

        $this->assertEquals(self::$userLenderAdmin->id, $response['data'][0]['creator']['id']);
    }
}
