<?php

namespace App\Models;

use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'description',
    'monthly_price',
    'annual_price',
    'currency',
    'max_users',
    'max_storage_bytes',
    'max_directions',
    'max_document_types',
    'max_ocr_pages_month',
    'has_api',
    'has_workflows',
    'has_advanced_audit',
    'has_priority_support',
    'has_dedicated_support',
    'has_sla',
    'has_custom_migration',
    'has_custom_integrations',
    'is_custom',
    'is_active',
    'sort_order',
])]
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'monthly_price' => 'integer',
        'annual_price' => 'integer',
        'max_users' => 'integer',
        'max_storage_bytes' => 'integer',
        'max_directions' => 'integer',
        'max_document_types' => 'integer',
        'max_ocr_pages_month' => 'integer',
        'has_api' => 'boolean',
        'has_workflows' => 'boolean',
        'has_advanced_audit' => 'boolean',
        'has_priority_support' => 'boolean',
        'has_dedicated_support' => 'boolean',
        'has_sla' => 'boolean',
        'has_custom_migration' => 'boolean',
        'has_custom_integrations' => 'boolean',
        'is_custom' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function isUnlimitedUsers(): bool
    {
        return $this->max_users === null;
    }

    public function isUnlimitedStorage(): bool
    {
        return $this->max_storage_bytes === null;
    }

    public function isUnlimitedDirections(): bool
    {
        return $this->max_directions === null;
    }

    public function isUnlimitedDocumentTypes(): bool
    {
        return $this->max_document_types === null;
    }

    public function isUnlimitedOcr(): bool
    {
        return $this->max_ocr_pages_month === null;
    }

    /**
     * Calculate annual savings compared to 12 monthly payments in FCFA.
     */
    public function getAnnualSavings(): int
    {
        if (! $this->monthly_price || ! $this->annual_price) {
            return 0;
        }

        $fullMonthly = $this->monthly_price * 12;

        return max(0, $fullMonthly - $this->annual_price);
    }
}
