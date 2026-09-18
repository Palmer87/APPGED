<?php

namespace App\Services\Ocr\Engines;

use App\Services\Ocr\Contracts\OcrEngineInterface;
use RuntimeException;

class TestingEngine implements OcrEngineInterface
{
    protected bool $available = true;

    protected ?string $fakeText = null;

    protected bool $shouldFail = false;

    protected string $failureMessage = 'Échec simulé du moteur OCR';

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function setAvailable(bool $available): static
    {
        $this->available = $available;

        return $this;
    }

    public function setFakeText(?string $text): static
    {
        $this->fakeText = $text;

        return $this;
    }

    public function shouldFail(bool $shouldFail = true, string $message = 'Échec simulé du moteur OCR'): static
    {
        $this->shouldFail = $shouldFail;
        $this->failureMessage = $message;

        return $this;
    }

    public function extractText(string $filePath, string $extension, array $options = []): string
    {
        if ($this->shouldFail) {
            throw new RuntimeException($this->failureMessage);
        }

        if (! $this->isAvailable()) {
            throw new RuntimeException("Le moteur OCR n'est pas disponible.");
        }

        if ($this->fakeText !== null) {
            return $this->fakeText;
        }

        // Default mock extraction with meaningful content
        $basename = pathinfo($filePath, PATHINFO_FILENAME);

        return "Texte extrait par OCR simulé pour le fichier {$basename}. Document vérifié et indexé pour la recherche.";
    }
}
