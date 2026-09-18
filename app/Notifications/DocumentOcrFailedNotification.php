<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\DocumentOcr;
use Illuminate\Notifications\Messages\MailMessage;

class DocumentOcrFailedNotification extends BaseGedNotification
{
    public const TYPE = 'document.ocr_failed';

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
            'title' => 'Échec du traitement OCR',
            'message' => "Le traitement OCR du document '{$this->document->name}' a échoué : {$this->ocr->error_message}",
            'document_id' => $this->document->id,
            'document_name' => $this->document->name,
            'ocr_id' => $this->ocr->id,
            'error_message' => $this->ocr->error_message,
            'url' => "/documents/{$this->document->id}",
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Échec OCR : {$this->document->name}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Le traitement OCR de votre document '{$this->document->name}' n'a pas pu aboutir.")
            ->line("Raison : {$this->ocr->error_message}")
            ->action('Consulter le document', url("/documents/{$this->document->id}"))
            ->line('Vous pouvez retenter le traitement depuis la page du document si nécessaire.');
    }
}
