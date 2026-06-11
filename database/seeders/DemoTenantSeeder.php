<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class DemoTenantSeeder extends Seeder
{
    /**
     * Seed one demo tenant with a realistic mix of invoices for screenshots.
     */
    public function run(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Northline Studio',
        ]);

        User::factory()->owner()->for($tenant)->create([
            'name' => 'Demo Owner',
            'email' => 'demo@invoiceflow.test',
        ]);

        User::factory()->for($tenant)->create([
            'name' => 'Demo Member',
            'email' => 'member@invoiceflow.test',
        ]);

        $clients = Client::factory()->count(7)->for($tenant)->create();

        $states = [
            ...array_fill(0, 3, InvoiceStatus::Draft),
            ...array_fill(0, 4, InvoiceStatus::Sent),
            ...array_fill(0, 5, InvoiceStatus::Paid),
            ...array_fill(0, 2, InvoiceStatus::Overdue),
            InvoiceStatus::Cancelled,
        ];

        foreach ($states as $index => $status) {
            $factory = Invoice::factory()
                ->for($tenant)
                ->for($clients->random());

            $factory = match ($status) {
                InvoiceStatus::Draft => $factory,
                InvoiceStatus::Sent => $factory->sent(),
                InvoiceStatus::Paid => $factory->paid(),
                InvoiceStatus::Overdue => $factory->overdue(),
                InvoiceStatus::Cancelled => $factory->cancelled(),
            };

            $invoice = $factory->create([
                'number' => sprintf('INV-%d-%04d', now()->year, $index + 1),
            ]);

            $items = InvoiceItem::factory()
                ->count(fake()->numberBetween(2, 4))
                ->for($tenant)
                ->for($invoice)
                ->state(new Sequence(fn (Sequence $sequence) => ['position' => $sequence->index]))
                ->create();

            $subtotal = (int) $items->sum('amount');
            $taxRate = fake()->randomElement([0, 0, 10]);
            $taxAmount = (int) round($subtotal * $taxRate / 100);
            $total = $subtotal + $taxAmount;

            $invoice->forceFill([
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'amount_paid' => $status === InvoiceStatus::Paid ? $total : 0,
            ])->save();

            if ($status === InvoiceStatus::Paid) {
                Payment::factory()->for($tenant)->for($invoice)->create([
                    'amount' => $total,
                    'currency' => $invoice->currency,
                    'paid_at' => $invoice->paid_at,
                ]);
            }
        }
    }
}
