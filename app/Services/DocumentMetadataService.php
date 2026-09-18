<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentMetadata;
use App\Models\MetadataDefinition;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DocumentMetadataService
{
    public function __construct(
        protected ?AuditService $auditService = null
    ) {
        $this->auditService = $this->auditService ?? app(AuditService::class);
    }

    /**
     * Set a single metadata value for a document.
     */
    public function setValue(Document $document, MetadataDefinition $definition, mixed $value): DocumentMetadata
    {
        if ($document->trashed()) {
            abort(404, 'Document is deleted');
        }

        if ($definition->trashed()) {
            abort(403, 'Metadata definition is deleted');
        }

        if ($document->organization_id !== $definition->organization_id) {
            abort(403, 'Metadata definition belongs to a different organization');
        }

        if (! $definition->is_active) {
            abort(422, "Cannot set value for inactive metadata definition '{$definition->key}'");
        }

        if ($definition->is_required && ($value === null || $value === '')) {
            abort(422, "Metadata field '{$definition->key}' is required");
        }

        $attributes = $this->prepareStorageAttributes($definition, $value);

        $result = DocumentMetadata::updateOrCreate(
            [
                'document_id' => $document->id,
                'metadata_definition_id' => $definition->id,
            ],
            $attributes
        );

        $this->auditService->success(
            action: 'document.metadata_updated',
            auditable: $document,
            target: $definition,
            newValues: [$definition->key => $value],
            description: "Metadata '{$definition->key}' updated on document '{$document->name}'."
        );

        return $result;
    }

    /**
     * Alias for setValue for compatibility.
     */
    public function setMetadata(Document $document, MetadataDefinition $definition, mixed $value, ?User $user = null): DocumentMetadata
    {
        return $this->setValue($document, $definition, $value);
    }

    /**
     * Set multiple metadata values atomically for a document.
     *
     * @param  array<string, mixed>  $values  associative array of [key => value]
     * @return Collection<int, DocumentMetadata>
     */
    public function setValues(Document $document, array $values): Collection
    {
        if ($document->trashed()) {
            abort(404, 'Document is deleted');
        }

        // Fetch all definitions of this organization indexed by key
        $definitions = MetadataDefinition::where('organization_id', $document->organization_id)
            ->get()
            ->keyBy('key');

        // Check required active definitions scoped to document type or organization
        $requiredDefinitions = $this->getRequiredDefinitionsForDocument($document, $definitions);
        foreach ($requiredDefinitions as $reqKey => $reqDef) {
            if (! array_key_exists($reqKey, $values) || $values[$reqKey] === null || $values[$reqKey] === '') {
                abort(422, "Required metadata field '{$reqKey}' is missing");
            }
        }

        // Pre-validate all provided keys and values BEFORE any write
        $preparedRecords = [];

        foreach ($values as $key => $value) {
            $definition = $definitions->get($key);

            if (! $definition) {
                abort(422, "Unknown metadata definition key '{$key}' for this organization");
            }

            if (! $definition->is_active) {
                abort(422, "Cannot set value for inactive metadata definition '{$key}'");
            }

            if ($definition->is_required && ($value === null || $value === '')) {
                abort(422, "Metadata field '{$key}' is required");
            }

            $storageAttributes = $this->prepareStorageAttributes($definition, $value);
            $preparedRecords[] = [
                'definition' => $definition,
                'attributes' => $storageAttributes,
            ];
        }

        // Atomic transaction: all writes succeed or all rollback
        $saved = DB::transaction(function () use ($document, $preparedRecords) {
            $saved = collect();

            foreach ($preparedRecords as $record) {
                $saved->push(DocumentMetadata::updateOrCreate(
                    [
                        'document_id' => $document->id,
                        'metadata_definition_id' => $record['definition']->id,
                    ],
                    $record['attributes']
                ));
            }

            return $saved;
        });

        $this->auditService->success(
            action: 'document.metadata_updated',
            auditable: $document,
            newValues: $values,
            description: count($values)." metadata field(s) updated on document '{$document->name}'."
        );

        return $saved;
    }

    /**
     * Remove a single metadata value from a document.
     */
    public function removeValue(Document $document, MetadataDefinition $definition): void
    {
        if ($document->organization_id !== $definition->organization_id) {
            abort(403, 'Metadata definition belongs to a different organization');
        }

        DocumentMetadata::where('document_id', $document->id)
            ->where('metadata_definition_id', $definition->id)
            ->delete();
    }

    /**
     * Remove all metadata values associated with a document.
     */
    public function removeAll(Document $document): void
    {
        DocumentMetadata::where('document_id', $document->id)->delete();
    }

    /**
     * Retrieve all normalized metadata values for a document.
     *
     * @return array<int, array{key: string, name: string, type: string, value: mixed}>
     */
    public function getDocumentMetadata(Document $document): array
    {
        $metadataRecords = DocumentMetadata::with('definition')
            ->where('document_id', $document->id)
            ->get();

        $result = [];

        foreach ($metadataRecords as $record) {
            if ($record->definition) {
                $result[] = [
                    'key' => $record->definition->key,
                    'name' => $record->definition->name,
                    'type' => $record->definition->type,
                    'value' => $record->getTypedValue(),
                ];
            }
        }

        return $result;
    }

    /**
     * Validate and map value to the corresponding typed SQL column.
     *
     * @return array<string, mixed>
     */
    private function prepareStorageAttributes(MetadataDefinition $definition, mixed $value): array
    {
        $storage = [
            'value_string' => null,
            'value_text' => null,
            'value_integer' => null,
            'value_decimal' => null,
            'value_boolean' => null,
            'value_date' => null,
            'value_datetime' => null,
        ];

        if ($value === null) {
            return $storage;
        }

        switch ($definition->type) {
            case 'string':
                if (! is_scalar($value) || is_bool($value)) {
                    abort(422, "Invalid value for string metadata '{$definition->key}'");
                }
                $str = (string) $value;
                if (mb_strlen($str) > 255) {
                    abort(422, "String metadata '{$definition->key}' exceeds maximum length of 255");
                }
                $storage['value_string'] = $str;
                break;

            case 'text':
                if (! is_scalar($value) || is_bool($value)) {
                    abort(422, "Invalid value for text metadata '{$definition->key}'");
                }
                $storage['value_text'] = (string) $value;
                break;

            case 'integer':
                if (is_bool($value) || (! is_int($value) && ! ctype_digit((string) $value) && filter_var($value, FILTER_VALIDATE_INT) === false)) {
                    abort(422, "Invalid integer value for metadata '{$definition->key}'");
                }
                $storage['value_integer'] = (int) $value;
                break;

            case 'decimal':
                if (is_bool($value) || ! is_numeric($value)) {
                    abort(422, "Invalid decimal value for metadata '{$definition->key}'");
                }
                $storage['value_decimal'] = (float) $value;
                break;

            case 'boolean':
                if (! is_bool($value) && ! in_array($value, [1, 0, '1', '0', 'true', 'false'], true)) {
                    abort(422, "Invalid boolean value for metadata '{$definition->key}'");
                }
                $storage['value_boolean'] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                break;

            case 'date':
                if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    abort(422, "Invalid date format for metadata '{$definition->key}', expected Y-m-d");
                }
                try {
                    $date = Carbon::createFromFormat('Y-m-d', $value);
                    if (! $date || $date->format('Y-m-d') !== $value) {
                        abort(422, "Invalid date value for metadata '{$definition->key}'");
                    }
                    $storage['value_date'] = $date->format('Y-m-d');
                } catch (\Throwable) {
                    abort(422, "Invalid date value for metadata '{$definition->key}'");
                }
                break;

            case 'datetime':
                if (! is_string($value)) {
                    abort(422, "Invalid datetime format for metadata '{$definition->key}'");
                }
                try {
                    $dt = Carbon::parse($value);
                    $storage['value_datetime'] = $dt->format('Y-m-d H:i:s');
                } catch (\Throwable) {
                    abort(422, "Invalid datetime value for metadata '{$definition->key}'");
                }
                break;

            default:
                abort(422, "Unsupported metadata definition type '{$definition->type}'");
        }

        return $storage;
    }

    /**
     * Get required definitions applicable for this document.
     */
    public function getRequiredDefinitionsForDocument(Document $document, Collection $definitions): Collection
    {
        $docType = $document->documentType ?? $document->folder?->getDocumentType();

        if ($docType) {
            $typeDefs = $docType->metadataDefinitions()->where('is_active', true)->get();

            return $typeDefs->filter(function ($def) {
                $required = $def->pivot->is_required ?? $def->is_required;

                return (bool) $required;
            })->keyBy('key');
        }

        // Untyped document: only require definitions not tied exclusively to document types
        $scopedDefIds = DB::table('folder_metadata_definition')->pluck('metadata_definition_id')->unique()->toArray();

        return $definitions->filter(function ($d) use ($scopedDefIds) {
            if (! $d->is_active || ! $d->is_required) {
                return false;
            }

            return ! in_array($d->id, $scopedDefIds, true);
        });
    }
}
