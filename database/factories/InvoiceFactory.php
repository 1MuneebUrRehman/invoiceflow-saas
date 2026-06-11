<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(200, 9_000) * 100;
        $issueDate = fake()->dateTimeBetween('-90 days', 'now');

        return [
            'tenant_id' => Tenant::factory(),
            'client_id' => fn (array $attributes) => Client::factory()->state([
                'tenant_id' => $attributes['tenant_id'],
            ]),
            'number' => 'INV-'.now()->year.'-'.fake()->unique()->numerify('####'),
            'status' => InvoiceStatus::Draft,
            'currency' => 'USD',
            'subtotal' => $subtotal,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => $subtotal,
            'amount_paid' => 0,
            'issue_date' => $issueDate,
            'due_date' => CarbonImmutable::instance($issueDate)->addDays(14),
            'notes' => null,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Sent,
            'sent_at' => $attributes['issue_date'],
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Paid,
            'sent_at' => $attributes['issue_date'],
            'paid_at' => CarbonImmutable::instance($attributes['issue_date'])->addDays(fake()->numberBetween(1, 12)),
            'amount_paid' => $attributes['total'],
        ]);
    }

    public function overdue(): static
    {
        $issueDate = fake()->dateTimeBetween('-75 days', '-30 days');

        return $this->state([
            'status' => InvoiceStatus::Overdue,
            'issue_date' => $issueDate,
            'due_date' => CarbonImmutable::instance($issueDate)->addDays(14),
            'sent_at' => $issueDate,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Cancelled,
            'sent_at' => $attributes['issue_date'],
        ]);
    }
}
