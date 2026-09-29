<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Notifications\Messages\MailMessage;

class TrialEndingSoonNotification extends BaseGedNotification
{
    public const TYPE = 'billing.trial_ending_soon';

    public function __construct(
        public Subscription $subscription,
        public int $daysRemaining
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
            'title' => "Fin d'essai gratuit imminente",
            'message' => "Votre période d'essai gratuit sur GEDAPP se termine dans {$this->daysRemaining} jour(s). Choisissez une offre pour continuer à profiter de toutes vos fonctionnalités.",
            'subscription_id' => $this->subscription->id,
            'days_remaining' => $this->daysRemaining,
            'url' => '/settings/subscription',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("GEDAPP — Votre essai gratuit se termine dans {$this->daysRemaining} jour(s)")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre période d'essai gratuit de 14 jours arrive à échéance dans {$this->daysRemaining} jour(s).")
            ->line("Pour éviter toute interruption de vos fonctionnalités avancées et conserver l'accès fluide de vos équipes, souscrivez dès maintenant à un abonnement.")
            ->action('Gérer mon abonnement', url('/settings/subscription'))
            ->line('Notre équipe commerciale reste à votre disposition pour vous conseiller.');
    }
}
