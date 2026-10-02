<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlatformUser;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected PlatformUser $owner;

    protected Organization $org;

    protected Plan $plan;

    protected Subscription $subscription;

    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = PlatformUser::create([
            'name' => 'Owner',
            'email' => 'owner@platform.test',
            'password' => Hash::make('password'),
            'role' => 'platform_owner',
            'is_active' => true,
        ]);

        $this->org = Organization::create([
            'name' => 'Invoice Org',
            'slug' => 'invoice-org',
            'status' => 'active',
        ]);

        $this->plan = Plan::firstOrCreate(
            ['slug' => 'invoice-plan'],
            [
                'name' => 'Invoice Plan',
                'monthly_price' => 25000,
                'annual_price' => 250000,
                'currency' => 'XOF',
                'is_active' => true,
            ]
        );

        $this->subscription = Subscription::create([
            'organization_id' => $this->org->id,
            'plan_id' => $this->plan->id,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'starts_at' => now(),
            'current_period_starts_at' => now(),
            'current_period_ends_at' => now()->addMonth(),
        ]);

        $this->invoice = Invoice::create([
            'organization_id' => $this->org->id,
            'subscription_id' => $this->subscription->id,
            'invoice_number' => 'INV-2026-0001',
            'amount' => 25000,
            'subtotal' => 25000,
            'total' => 25000,
            'tax' => 0,
            'currency' => 'XOF',
            'status' => 'issued',
            'due_at' => now()->addDays(15),
        ]);
    }

    public function test_can_list_invoices(): void
    {
        $response = $this->actingAs($this->owner, 'platform')
            ->get('/platform/invoices');

        $response->assertOk();
    }

    public function test_can_view_invoice_details(): void
    {
        $response = $this->actingAs($this->owner, 'platform')
            ->get("/platform/invoices/{$this->invoice->id}");

        $response->assertOk();
    }

    public function test_can_mark_invoice_as_paid_and_creates_payment(): void
    {
        $response = $this->actingAs($this->owner, 'platform')
            ->post("/platform/invoices/{$this->invoice->id}/mark-paid");

        $response->assertRedirect();
        $this->invoice->refresh();

        $this->assertEquals('paid', $this->invoice->status);
        $this->assertNotNull($this->invoice->paid_at);

        // Payment record was automatically generated
        $this->assertDatabaseHas('payments', [
            'organization_id' => $this->org->id,
            'invoice_id' => $this->invoice->id,
            'amount' => 25000,
            'status' => 'paid',
        ]);
    }
}
