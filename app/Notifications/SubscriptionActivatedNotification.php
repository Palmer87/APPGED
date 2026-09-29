<?php

namespace App\Notifications;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Notifications\Messages\MailMessage;

class SubscriptionActivatedNotification extends BaseGedNotification
{
    public const TYPE = 'billing.subscription_activated';

    public function __construct(
        public Subscription $subscription,
        public Plan $plan
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
            'title' => 'Abonnement activé',
            'message' => "Félicitations ! Votre organisation est désormais abonnée au plan {$this->plan->name}.",
            'subscription_id' => $this->subscription->id,
            'plan_name' => $this->plan->name,
            'cycle' => $this->subscription->billing_cycle,
            'url' => '/settings/subscription',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("GEDAPP — Abonnement {$this->plan->name} activé avec succès")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Nous vous confirmons l'activation de votre abonnement au plan {$this->plan->name} sur GEDAPP.")
            ->line('Toutes les fonctionnalités et quotas de votre offre sont désormais débloqués pour votre organisation.')
            ->action('Accéder à mon espace', url('/dashboard'))
            ->line('Merci de faire confiance à GEDAPP pour votre gestion documentaire.');
    }
}
