<?php

namespace App\Enums;

enum AccessScopeType: string
{
    case Organization = 'organization';
    case Direction = 'direction';
    case Service = 'service';
    case DocumentType = 'document_type';
    case Folder = 'folder';
    case Document = 'document';

    public function label(): string
    {
        return match ($this) {
            self::Organization => 'Organisation entière',
            self::Direction => 'Direction',
            self::Service => 'Service',
            self::DocumentType => 'Type documentaire',
            self::Folder => 'Dossier spécifique',
            self::Document => 'Document spécifique',
        };
    }
}
