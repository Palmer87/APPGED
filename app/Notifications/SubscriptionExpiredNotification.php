<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Notifications\Messages\MailMessage;

class SubscriptionExpiredNotification extends BaseGedNotification
{
    public const TYPE = 'billing.subscription_expired';

    public function __construct(
        public Subscription $subscription
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
            'title' => 'Abonnement expiré',
            'message' => "Votre période d'abonnement ou d'essai gratuit a expiré. Veuillez renouveler votre abonnement pour débloquer toutes vos fonctionnalités.",
            'subscription_id' => $this->subscription->id,
            'url' => '/settings/subscription',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('GEDAPP — Votre abonnement a expiré')
            ->greeting("Bonjour {$notifiable->name},")
            ->line('La période active de votre organisation sur GEDAPP est arrivée à son terme.')
            ->line("Vos documents et données restent conservés en toute sécurité. Les fonctionnalités d'ajout ou de création avancée sont temporairement suspendues.")
            ->line('Pour reprendre vos activités sans restriction, réactivez votre abonnement.')
            ->action('Réactiver mon abonnement', url('/settings/subscription'))
            ->line('Notre équipe reste disponible pour vous accompagner.');
    }
}
