<?php

namespace App\Exceptions;

use Exception;

class SubscriptionLimitExceededException extends Exception
{
    public function __construct(
        public string $limitType,
        public int|float $currentUsage,
        public int|float $limit,
        public ?string $planName = 'Essentiel',
        ?string $customMessage = null
    ) {
        $message = $customMessage ?? match ($limitType) {
            'users' => "Votre abonnement {$planName} autorise jusqu'à {$limit} utilisateurs. Passez au plan supérieur pour ajouter davantage d'utilisateurs.",
            'storage' => "Votre quota de stockage sur le plan {$planName} est atteint. Passez au plan supérieur pour augmenter votre espace.",
            'directions' => "Votre abonnement {$planName} autorise jusqu'à {$limit} directions. Passez au plan supérieur pour créer de nouvelles directions.",
            'document_types' => "Votre abonnement {$planName} autorise jusqu'à {$limit} types documentaires. Passez au plan supérieur pour en ajouter.",
            'ocr_pages' => "Votre quota de pages OCR pour ce mois ({$limit} pages) est atteint pour le plan {$planName}. Passez au plan supérieur pour traiter plus de pages.",
            default => "La limite de votre abonnement ({$limitType}) a été atteinte. Veuillez mettre à niveau votre plan.",
        };

        parent::__construct($message, 403);
    }
}
