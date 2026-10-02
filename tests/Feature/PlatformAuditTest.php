<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PlatformAuditLog;
use App\Models\PlatformUser;
use App\Services\PlatformAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformAuditTest extends TestCase
{
    use RefreshDatabase;

    protected PlatformUser $owner;

    protected Organization $org;

    protected PlatformAuditService $auditService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auditService = app(PlatformAuditService::class);

        $this->owner = PlatformUser::create([
            'name' => 'Owner',
            'email' => 'owner@platform.test',
            'password' => Hash::make('password'),
            'role' => 'platform_owner',
            'is_active' => true,
        ]);

        $this->org = Organization::create([
            'name' => 'Audit Org',
            'slug' => 'audit-org',
            'status' => 'active',
        ]);
    }

    public function test_can_list_platform_audit_logs(): void
    {
        PlatformAuditLog::create([
            'platform_user_id' => $this->owner->id,
            'organization_id' => $this->org->id,
            'action' => 'platform.organization.suspended',
            'description' => 'Test log description',
        ]);

        $response = $this->actingAs($this->owner, 'platform')
            ->get('/platform/audit');

        $response->assertOk();
    }

    public function test_audit_service_sanitizes_sensitive_keys_from_payloads(): void
    {
        $sensitiveData = [
            'name' => 'Acme',
            'password' => 'supersecret123',
            'password_confirmation' => 'supersecret123',
            'token' => 'Bearer sensitive-token-here',
            'r2_secret' => 'r2-secret-key-abcdef',
            'access_token' => 'jwt-token-value',
        ];

        $log = $this->auditService->log(
            action: 'platform.security.test',
            description: 'Sanitization verification',
            newValues: $sensitiveData,
            organizationId: $this->org->id,
            actor: $this->owner
        );

        $this->assertEquals('***REDACTED***', $log->new_values['password']);
        $this->assertEquals('***REDACTED***', $log->new_values['password_confirmation']);
        $this->assertEquals('***REDACTED***', $log->new_values['token']);
        $this->assertEquals('***REDACTED***', $log->new_values['r2_secret']);
        $this->assertEquals('***REDACTED***', $log->new_values['access_token']);
        $this->assertEquals('Acme', $log->new_values['name']);
    }

    public function test_can_filter_audit_logs_by_action(): void
    {
        PlatformAuditLog::create([
            'platform_user_id' => $this->owner->id,
            'action' => 'platform.plan.created',
            'description' => 'Created plan A',
        ]);

        PlatformAuditLog::create([
            'platform_user_id' => $this->owner->id,
            'action' => 'platform.plan.updated',
            'description' => 'Updated plan A',
        ]);

        $response = $this->actingAs($this->owner, 'platform')
            ->get('/platform/audit?action=plan.created');

        $response->assertOk();
    }
}
