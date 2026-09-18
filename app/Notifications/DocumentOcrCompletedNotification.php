<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\DocumentOcr;
use Illuminate\Notifications\Messages\MailMessage;

class DocumentOcrCompletedNotification extends BaseGedNotification
{
    public const TYPE = 'document.ocr_completed';

    public function __construct(
        public Document $document,
        public DocumentOcr $ocr
    ) {}

    public function getType(): string
    {
        return self::TYPE;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => self::TYPE,
            'title' => 'Texte OCR indexé',
            'message' => "Le texte du document '{$this->document->name}' a été extrait avec succès ({$this->ocr->word_count} mots) et est désormais disponible pour la recherche.",
            'document_id' => $this->document->id,
            'document_name' => $this->document->name,
            'ocr_id' => $this->ocr->id,
            'word_count' => $this->ocr->word_count,
            'url' => "/documents/{$this->document->id}",
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("OCR terminé : {$this->document->name}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Le texte de votre document '{$this->document->name}' a été analysé et extrait avec succès.")
            ->line("Nombre de mots indexés : {$this->ocr->word_count}.")
            ->action('Consulter le document', url("/documents/{$this->document->id}"))
            ->line('Le document est désormais interrogeable via la recherche textuelle.');
    }
}
