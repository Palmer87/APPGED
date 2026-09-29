<?php

namespace App\Notifications;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Notifications\Messages\MailMessage;

class SubscriptionPlanChangedNotification extends BaseGedNotification
{
    public const TYPE = 'billing.plan_changed';

    public function __construct(
        public Subscription $subscription,
        public Plan $oldPlan,
        public Plan $newPlan
    ) {}

    public function getType(): string
    {
        return self::TYPE;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => self::TYPE,
            'title' => 'Changement de plan effectué',
            'message' => "Votre abonnement a été modifié avec succès du plan {$this->oldPlan->name} vers le plan {$this->newPlan->name}.",
            'subscription_id' => $this->subscription->id,
            'old_plan' => $this->oldPlan->name,
            'new_plan' => $this->newPlan->name,
            'cycle' => $this->subscription->billing_cycle,
            'url' => '/settings/subscription',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("GEDAPP — Confirmation de changement d'offre ({$this->newPlan->name})")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Le plan d'abonnement de votre organisation a été mis à jour de {$this->oldPlan->name} vers {$this->newPlan->name}.")
            ->line('Vos nouvelles limites et fonctionnalités sont immédiatement appliquées.')
            ->action('Voir mon abonnement', url('/settings/subscription'))
            ->line('Merci de votre fidélité.');
    }
}
