<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\BillingPermissionSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseLeadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            PlanSeeder::class,
            BillingPermissionSeeder::class,
        ]);
    }

    public function test_enterprise_page_is_accessible(): void
    {
        $response = $this->get('/enterprise');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Public/Enterprise'));
    }

    public function test_submitting_enterprise_lead_creates_lead_record(): void
    {
        $payload = [
            'name' => 'Paul Pogba',
            'company' => 'Global Logistics SA',
            'email' => 'paul.pogba@globallogistics.com',
            'phone' => '+225 05 01 02 03 04',
            'estimated_users' => '50-200',
            'needs' => 'OCR haute volumétrie, connecteurs ERP SAP, SLA garanti 99.9%',
            'message' => 'Nous souhaitons une démonstration sur site la semaine prochaine.',
        ];

        $response = $this->post('/enterprise', $payload);

        $response->assertRedirect();
        $response->assertSessionHas('message');

        $this->assertDatabaseHas('leads', [
            'name' => 'Paul Pogba',
            'company' => 'Global Logistics SA',
            'email' => 'paul.pogba@globallogistics.com',
            'status' => 'new',
        ]);
    }

    public function test_submitting_enterprise_lead_via_contact_alias(): void
    {
        $payload = [
            'name' => 'Alice Koffi',
            'company' => 'Banque Atlantique',
            'email' => 'alice.koffi@banqueatlantique.ci',
            'phone' => '+225 01 02 03 04 05',
            'estimated_users' => '200+',
            'needs' => 'Hébergement souverain et migration documentaire.',
        ];

        $response = $this->post('/enterprise/contact', $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('leads', [
            'email' => 'alice.koffi@banqueatlantique.ci',
        ]);
    }

    public function test_enterprise_lead_does_not_create_organization_or_subscription(): void
    {
        $initialOrgsCount = Organization::count();
        $initialSubsCount = Subscription::count();
        $initialUsersCount = User::count();

        $this->post('/enterprise', [
            'name' => 'Prospect Enterprise',
            'company' => 'Grande Entreprise SA',
            'email' => 'prospect@enterprise.com',
            'phone' => '+225 07 11 22 33 44',
            'estimated_users' => '100+',
            'needs' => 'Audit complet',
        ]);

        $this->assertSame($initialOrgsCount, Organization::count());
        $this->assertSame($initialSubsCount, Subscription::count());
        $this->assertSame($initialUsersCount, User::count());
    }

    public function test_enterprise_lead_validation_errors(): void
    {
        $response = $this->post('/enterprise', [
            'name' => '',
            'company' => '',
            'email' => 'invalid-email',
        ]);

        $response->assertSessionHasErrors(['name', 'company', 'email']);
    }
}
