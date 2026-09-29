<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = 19000;

        return [
            'organization_id' => Organization::factory(),
            'subscription_id' => Subscription::factory(),
            'invoice_number' => 'INV-'.strtoupper(fake()->unique()->bothify('####-????')),
            'amount' => $amount,
            'currency' => 'XOF',
            'tax' => 0,
            'subtotal' => $amount,
            'total' => $amount,
            'status' => 'paid',
            'paid_at' => now(),
            'due_at' => now()->addDays(7),
            'provider' => 'manual',
            'provider_payment_id' => 'MAN-'.fake()->randomNumber(6, true),
            'metadata' => null,
        ];
    }
}
