<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 40);
        $unitPrice = fake()->numberBetween(50, 250) * 100;

        return [
            'tenant_id' => Tenant::factory(),
            'invoice_id' => fn (array $attributes) => Invoice::factory()->state([
                'tenant_id' => $attributes['tenant_id'],
            ]),
            'description' => fake()->randomElement([
                'Design consultation',
                'Frontend development',
                'Backend development',
                'Brand identity design',
                'Monthly retainer',
                'Technical audit',
                'Content strategy workshop',
            ]),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'amount' => (int) round($quantity * $unitPrice),
            'position' => 0,
        ];
    }
}
