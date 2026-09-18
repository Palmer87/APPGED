<?php

namespace App\Enums;

enum FolderType: string
{
    case Standard = 'standard';
    case Department = 'department';
    case DocumentType = 'document_type';

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Dossier standard',
            self::Department => 'Direction',
            self::DocumentType => 'Type documentaire',
        };
    }

    public function isStandard(): bool
    {
        return $this === self::Standard;
    }

    public function isDepartment(): bool
    {
        return $this === self::Department;
    }

    public function isDocumentType(): bool
    {
        return $this === self::DocumentType;
    }
}
