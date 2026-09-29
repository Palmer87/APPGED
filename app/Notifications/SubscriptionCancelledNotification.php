<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Notifications\Messages\MailMessage;

class SubscriptionCancelledNotification extends BaseGedNotification
{
    public const TYPE = 'billing.subscription_cancelled';

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
        $endsAt = $this->subscription->current_period_ends_at?->format('d/m/Y') ?? 'la fin de la période';

        return [
            'type' => self::TYPE,
            'title' => 'Résiliation de l\'abonnement',
            'message' => "Le renouvellement automatique de votre abonnement a été désactivé. Vos accès restent actifs jusqu'au {$endsAt}.",
            'subscription_id' => $this->subscription->id,
            'ends_at' => $endsAt,
            'url' => '/settings/subscription',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $endsAt = $this->subscription->current_period_ends_at?->format('d/m/Y') ?? 'la fin de la période';

        return (new MailMessage)
            ->subject('GEDAPP — Confirmation de résiliation de votre abonnement')
            ->greeting("Bonjour {$notifiable->name},")
            ->line('Nous vous confirmons l\'annulation du renouvellement automatique de votre abonnement.')
            ->line("Vos services restent pleinement fonctionnels jusqu'au {$endsAt}.")
            ->line('Vous pouvez réactiver votre abonnement à tout moment d\'un simple clic avant cette date.')
            ->action('Gérer mon abonnement', url('/settings/subscription'))
            ->line('Nous espérons vous revoir très bientôt parmi nos clients.');
    }
}
