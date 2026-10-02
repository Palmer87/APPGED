<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PlatformUser;
use App\Models\SupportTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformSupportTest extends TestCase
{
    use RefreshDatabase;

    protected PlatformUser $owner;

    protected PlatformUser $supportUser;

    protected Organization $org;

    protected SupportTicket $ticket;

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

        $this->supportUser = PlatformUser::create([
            'name' => 'Agent Support',
            'email' => 'agent@platform.test',
            'password' => Hash::make('password'),
            'role' => 'platform_support',
            'is_active' => true,
        ]);

        $this->org = Organization::create([
            'name' => 'Support Org',
            'slug' => 'support-org',
            'status' => 'active',
        ]);

        $this->ticket = SupportTicket::create([
            'organization_id' => $this->org->id,
            'ticket_number' => 'TCK-2026-0001',
            'subject' => 'Problème de téléversement',
            'description' => 'Impossible de téléverser un fichier de 50 Mo',
            'priority' => 'high',
            'status' => 'open',
            'platform_user_id' => $this->supportUser->id,
        ]);
    }

    public function test_can_list_support_tickets(): void
    {
        $response = $this->actingAs($this->owner, 'platform')
            ->get('/platform/support');

        $response->assertOk();
    }

    public function test_can_create_a_support_ticket(): void
    {
        $response = $this->actingAs($this->owner, 'platform')
            ->post('/platform/support', [
                'organization_id' => $this->org->id,
                'subject' => 'Aide configuration workflows',
                'description' => 'Besoin d\'assistance pour la mise en place d\'un circuit de validation.',
                'priority' => 'normal',
                'platform_user_id' => $this->supportUser->id,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('support_tickets', [
            'organization_id' => $this->org->id,
            'subject' => 'Aide configuration workflows',
            'priority' => 'normal',
            'status' => 'open',
        ]);
    }

    public function test_can_update_ticket_status_to_resolved(): void
    {
        $response = $this->actingAs($this->owner, 'platform')
            ->put("/platform/support/{$this->ticket->id}", [
                'status' => 'resolved',
                'priority' => 'high',
                'platform_user_id' => $this->supportUser->id,
            ]);

        $response->assertRedirect();
        $this->ticket->refresh();

        $this->assertEquals('resolved', $this->ticket->status);
        $this->assertNotNull($this->ticket->resolved_at);
    }
}
