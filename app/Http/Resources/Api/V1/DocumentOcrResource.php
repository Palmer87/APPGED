<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentOcrResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_id' => $this->document_id,
            'document_version_id' => $this->document_version_id,
            'organization_id' => $this->organization_id,
            'status' => is_string($this->status) ? $this->status : $this->status->value,
            'status_label' => is_string($this->status) ? $this->status : $this->status->label(),
            'has_text' => ! empty($this->extracted_text),
            'word_count' => $this->word_count ?? 0,
            'confidence' => $this->confidence,
            'language' => $this->language,
            'execution_time_ms' => $this->execution_time_ms,
            'extracted_text' => $this->extracted_text,
            'error_message' => $this->error_message,
            'processed_at' => $this->processed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
