<?php

namespace Database\Factories;

use App\Enums\PaymentProvider;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'invoice_id' => fn (array $attributes) => Invoice::factory()->state([
                'tenant_id' => $attributes['tenant_id'],
            ]),
            'amount' => fake()->numberBetween(200, 9_000) * 100,
            'currency' => 'USD',
            'provider' => PaymentProvider::Stripe,
            'provider_reference' => 'pi_'.Str::random(24),
            'provider_payload' => null,
            'paid_at' => fake()->dateTimeBetween('-60 days', 'now'),
        ];
    }

    public function manual(): static
    {
        return $this->state([
            'provider' => PaymentProvider::Manual,
            'provider_reference' => null,
        ]);
    }
}
