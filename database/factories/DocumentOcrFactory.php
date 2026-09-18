<?php

namespace Database\Factories;

use App\Enums\OcrStatus;
use App\Models\Document;
use App\Models\DocumentOcr;
use App\Models\DocumentVersion;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentOcr>
 */
class DocumentOcrFactory extends Factory
{
    protected $model = DocumentOcr::class;

    public function definition(): array
    {
        $text = fake()->paragraphs(3, true);

        return [
            'organization_id' => Organization::factory(),
            'document_id' => Document::factory(),
            'document_version_id' => null,
            'status' => OcrStatus::Completed,
            'extracted_text' => $text,
            'error_message' => null,
            'word_count' => str_word_count($text),
            'confidence' => fake()->randomFloat(2, 70, 99),
            'language' => 'fra+eng',
            'execution_time_ms' => fake()->numberBetween(200, 3000),
            'processed_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => OcrStatus::Pending,
            'extracted_text' => null,
            'error_message' => null,
            'word_count' => 0,
            'confidence' => null,
            'execution_time_ms' => null,
            'processed_at' => null,
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn () => [
            'status' => OcrStatus::Processing,
            'extracted_text' => null,
            'error_message' => null,
            'word_count' => 0,
            'confidence' => null,
            'execution_time_ms' => null,
            'processed_at' => null,
        ]);
    }

    public function completed(?string $text = null): static
    {
        $extracted = $text ?? fake()->paragraphs(3, true);

        return $this->state(fn () => [
            'status' => OcrStatus::Completed,
            'extracted_text' => $extracted,
            'error_message' => null,
            'word_count' => str_word_count($extracted),
            'confidence' => 95.0,
            'execution_time_ms' => 1200,
            'processed_at' => now(),
        ]);
    }

    public function failed(?string $errorMessage = null): static
    {
        return $this->state(fn () => [
            'status' => OcrStatus::Failed,
            'extracted_text' => null,
            'error_message' => $errorMessage ?? 'Le binaire Tesseract OCR n\'est pas installé sur le serveur.',
            'word_count' => 0,
            'confidence' => null,
            'execution_time_ms' => null,
            'processed_at' => now(),
        ]);
    }

    public function skipped(?string $reason = null): static
    {
        return $this->state(fn () => [
            'status' => OcrStatus::Skipped,
            'extracted_text' => null,
            'error_message' => $reason ?? 'Format de fichier non pris en charge pour l\'OCR en V1.',
            'word_count' => 0,
            'confidence' => null,
            'execution_time_ms' => null,
            'processed_at' => now(),
        ]);
    }

    public function forVersion(DocumentVersion $version): static
    {
        return $this->state(fn () => [
            'document_id' => $version->document_id,
            'document_version_id' => $version->id,
            'organization_id' => $version->document->organization_id,
        ]);
    }
}
