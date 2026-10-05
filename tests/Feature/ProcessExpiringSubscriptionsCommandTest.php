<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use App\Notifications\SubscriptionExpiredNotification;
use App\Notifications\TrialEndingSoonNotification;
use Database\Seeders\BillingPermissionSeeder;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProcessExpiringSubscriptionsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            PlanSeeder::class,
            RolePermissionSeeder::class,
            BillingPermissionSeeder::class,
        ]);
    }

    protected function createAdminForOrg(Organization $org): User
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        Role::findOrCreate('admin', 'web');

        $admin = User::factory()->create(['organization_id' => $org->id]);
        $admin->assignRole('admin');

        return $admin;
    }

    /**
     * 1. La commande existe et est enregistrée dans Artisan.
     */
    public function test_command_exists_in_artisan(): void
    {
        $commands = Artisan::all();

        $this->assertArrayHasKey('app:process-expiring-subscriptions', $commands);
    }

    /**
     * 2. La commande peut être exécutée avec succès via artisan.
     */
    public function test_command_can_be_executed_successfully(): void
    {
        $this->artisan('app:process-expiring-subscriptions')
            ->expectsOutputToContain('Processing expiring trials and subscriptions...')
            ->expectsOutputToContain('Completed successfully')
            ->assertSuccessful();
    }

    /**
     * 3. Les abonnements et essais concernés sont correctement identifiés.
     */
    public function test_expiring_trials_and_expired_subscriptions_are_identified(): void
    {
        Notification::fake();

        // Organisation 1: Essai avec 2 jours restants (doit recevoir un avertissement)
        $org1 = Organization::factory()->create(['name' => 'Org Ending Soon']);
        $sub1 = $org1->currentSubscription;
        $sub1->update([
            'status' => 'trialing',
            'trial_ends_at' => now()->addDays(2),
        ]);

        // Organisation 2: Essai terminé hier (doit passer à expired)
        $org2 = Organization::factory()->create(['name' => 'Org Expired']);
        $sub2 = $org2->currentSubscription;
        $sub2->update([
            'status' => 'trialing',
            'trial_ends_at' => now()->subDay(),
        ]);

        // Organisation 3: Essai avec 10 jours restants (ne doit pas être alerté)
        $org3 = Organization::factory()->create(['name' => 'Org Normal']);
        $sub3 = $org3->currentSubscription;
        $sub3->update([
            'status' => 'trialing',
            'trial_ends_at' => now()->addDays(10),
        ]);

        $this->artisan('app:process-expiring-subscriptions')->assertSuccessful();

        $this->assertSame('trialing', $sub1->fresh()->status);
        $this->assertSame('expired', $sub2->fresh()->status);
        $this->assertNotNull($sub2->fresh()->ended_at);
        $this->assertSame('trialing', $sub3->fresh()->status);
    }

    /**
     * 4. Les notifications appropriées sont envoyées aux administrateurs.
     */
    public function test_appropriate_notifications_are_dispatched(): void
    {
        Notification::fake();

        // Org 1: Trial ending soon
        $org1 = Organization::factory()->create(['name' => 'Org 1']);
        $admin1 = $this->createAdminForOrg($org1);

        $sub1 = $org1->currentSubscription;
        $sub1->update([
            'status' => 'trialing',
            'trial_ends_at' => now()->addDays(3),
        ]);

        // Org 2: Expired trial
        $org2 = Organization::factory()->create(['name' => 'Org 2']);
        $admin2 = $this->createAdminForOrg($org2);

        $sub2 = $org2->currentSubscription;
        $sub2->update([
            'status' => 'trialing',
            'trial_ends_at' => now()->subDay(),
        ]);

        $this->artisan('app:process-expiring-subscriptions')->assertSuccessful();

        Notification::assertSentTo($admin1, TrialEndingSoonNotification::class, function ($notification) use ($sub1) {
            return $notification->subscription->id === $sub1->id && $notification->daysRemaining <= 3;
        });

        Notification::assertSentTo($admin2, SubscriptionExpiredNotification::class, function ($notification) use ($sub2) {
            return $notification->subscription->id === $sub2->id;
        });
    }

    /**
     * 5. Les notifications ne sont pas envoyées plusieurs fois inutilement (idempotence des notifications).
     */
    public function test_notifications_are_not_sent_multiple_times(): void
    {
        Notification::fake();

        $org = Organization::factory()->create(['name' => 'Org Ending Soon']);
        $admin = $this->createAdminForOrg($org);

        $sub = $org->currentSubscription;
        $sub->update([
            'status' => 'trialing',
            'trial_ends_at' => now()->addDays(2),
        ]);

        // Premier passage: la notification d'avertissement doit être envoyée
        $this->artisan('app:process-expiring-subscriptions')->assertSuccessful();
        Notification::assertSentToTimes($admin, TrialEndingSoonNotification::class, 1);

        // Deuxième passage: la notification ne doit PAS être réenvoyée
        $this->artisan('app:process-expiring-subscriptions')->assertSuccessful();
        Notification::assertSentToTimes($admin, TrialEndingSoonNotification::class, 1);
    }

    /**
     * 6. Les abonnements d'une organisation ne peuvent pas affecter une autre organisation (isolation tenant).
     */
    public function test_tenant_isolation_subscriptions_do_not_leak_across_organizations(): void
    {
        Notification::fake();

        $orgA = Organization::factory()->create(['name' => 'Org A']);
        $adminA = $this->createAdminForOrg($orgA);

        $orgB = Organization::factory()->create(['name' => 'Org B']);
        $adminB = $this->createAdminForOrg($orgB);

        // Org A expires
        $subA = $orgA->currentSubscription;
        $subA->update([
            'status' => 'trialing',
            'trial_ends_at' => now()->subDay(),
        ]);

        // Org B has an active paid subscription
        $proPlan = Plan::where('slug', 'professional')->firstOrFail();
        $subB = $orgB->currentSubscription;
        $subB->update([
            'plan_id' => $proPlan->id,
            'status' => 'active',
            'current_period_ends_at' => now()->addMonth(),
        ]);

        $this->artisan('app:process-expiring-subscriptions')->assertSuccessful();

        // Org A is expired and admin A notified
        $this->assertSame('expired', $subA->fresh()->status);
        Notification::assertSentTo($adminA, SubscriptionExpiredNotification::class);

        // Org B is untouched and admin B receives NOTHING
        $this->assertSame('active', $subB->fresh()->status);
        Notification::assertNotSentTo($adminB, SubscriptionExpiredNotification::class);
        Notification::assertNotSentTo($adminB, TrialEndingSoonNotification::class);
    }

    /**
     * 7. Un abonnement déjà traité reste idempotent lors des exécutions répétées.
     */
    public function test_expired_subscription_processing_is_strictly_idempotent(): void
    {
        Notification::fake();

        $org = Organization::factory()->create(['name' => 'Org Already Expired']);
        $admin = $this->createAdminForOrg($org);

        $sub = $org->currentSubscription;
        $sub->update([
            'status' => 'trialing',
            'trial_ends_at' => now()->subDays(5),
        ]);

        // Premier passage
        $this->artisan('app:process-expiring-subscriptions')->assertSuccessful();
        $this->assertSame('expired', $sub->fresh()->status);
        $endedAtFirst = $sub->fresh()->ended_at;
        $this->assertNotNull($endedAtFirst);
        Notification::assertSentToTimes($admin, SubscriptionExpiredNotification::class, 1);

        // Deuxième passage
        $this->artisan('app:process-expiring-subscriptions')->assertSuccessful();
        $this->assertSame('expired', $sub->fresh()->status);
        $this->assertEquals($endedAtFirst->timestamp, $sub->fresh()->ended_at->timestamp);
        // Toujours une seule notification au total
        Notification::assertSentToTimes($admin, SubscriptionExpiredNotification::class, 1);
    }

    /**
     * 8. Le scheduler contient bien les trois tâches quotidiennes configurées sur un seul serveur.
     */
    public function test_scheduler_contains_all_three_daily_tasks_on_one_server(): void
    {
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());

        // Tâche 1: sanctum:prune-expired
        $sanctumEvent = $events->first(fn ($event) => str_contains((string) $event->command, 'sanctum:prune-expired --hours=24'));
        $this->assertNotNull($sanctumEvent, 'Task sanctum:prune-expired should be registered in scheduler.');
        $this->assertSame('0 0 * * *', $sanctumEvent->expression);
        $this->assertTrue($sanctumEvent->onOneServer, 'sanctum:prune-expired must have onOneServer enabled.');

        // Tâche 2: queue:prune-failed
        $queueEvent = $events->first(fn ($event) => str_contains((string) $event->command, 'queue:prune-failed --hours=720'));
        $this->assertNotNull($queueEvent, 'Task queue:prune-failed should be registered in scheduler.');
        $this->assertSame('0 0 * * *', $queueEvent->expression);
        $this->assertTrue($queueEvent->onOneServer, 'queue:prune-failed must have onOneServer enabled.');

        // Tâche 3: app:process-expiring-subscriptions
        $subEvent = $events->first(fn ($event) => str_contains((string) $event->command, 'app:process-expiring-subscriptions'));
        $this->assertNotNull($subEvent, 'Task app:process-expiring-subscriptions should be registered in scheduler.');
        $this->assertSame('0 0 * * *', $subEvent->expression);
        $this->assertTrue($subEvent->onOneServer, 'app:process-expiring-subscriptions must have onOneServer enabled.');
    }
}
