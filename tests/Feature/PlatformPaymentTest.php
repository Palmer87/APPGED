<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Payment;
use App\Models\PlatformUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected PlatformUser $owner;

    protected Organization $org;

    protected Payment $payment;

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
            'name' => 'Payment Org',
            'slug' => 'payment-org',
            'status' => 'active',
        ]);

        $this->payment = Payment::create([
            'organization_id' => $this->org->id,
            'amount' => 50000,
            'currency' => 'XOF',
            'status' => 'paid',
            'provider' => 'manual',
            'provider_reference' => 'PAY-TEST-001',
            'paid_at' => now(),
        ]);
    }

    public function test_can_list_payments(): void
    {
        $response = $this->actingAs($this->owner, 'platform')
            ->get('/platform/payments');

        $response->assertOk();
    }

    public function test_can_record_a_manual_payment(): void
    {
        $response = $this->actingAs($this->owner, 'platform')
            ->post('/platform/payments', [
                'organization_id' => $this->org->id,
                'amount' => 75000,
                'currency' => 'XOF',
                'provider' => 'manual',
                'provider_reference' => 'CHQ-2026-99',
                'paid_at' => now()->toDateString(),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('payments', [
            'organization_id' => $this->org->id,
            'amount' => 75000,
            'provider_reference' => 'CHQ-2026-99',
            'status' => 'paid',
        ]);
    }

    public function test_validates_required_payment_fields(): void
    {
        $response = $this->actingAs($this->owner, 'platform')
            ->post('/platform/payments', [
                'amount' => -100, // invalid negative amount
            ]);

        $response->assertSessionHasErrors(['organization_id', 'amount']);
    }
}
