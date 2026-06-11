<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentProvider;
use App\Enums\TenantPlan;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\CurrentTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoTenantSeeder extends Seeder
{
    public function run(): void
    {
        $currentTenant = app(CurrentTenant::class);

        // ── Primary demo tenant (Pro plan) ────────────────────────────────────
        $tenant = Tenant::factory()->create([
            'name' => 'Northline Studio',
            'default_currency' => 'USD',
            'plan' => TenantPlan::Pro,
        ]);

        // Set tenant context so BelongsToTenant stamps tenant_id on all related models.
        $currentTenant->set($tenant);

        $owner = User::factory()->owner()->for($tenant)->create([
            'name' => 'Alex Morgan',
            'email' => 'demo@invoiceflow.test',
            'password' => bcrypt('password'),
        ]);

        User::factory()->for($tenant)->create([
            'name' => 'Jamie Lee',
            'email' => 'member@invoiceflow.test',
            'password' => bcrypt('password'),
            'role' => UserRole::Member,
        ]);

        // Issue an API token so the API can be tested immediately
        $owner->createToken('Demo API Token')->plainTextToken;

        $clients = collect([
            ['name' => 'Horizon Media', 'company_name' => 'Horizon Media Group', 'email' => 'billing@horizonmedia.com', 'currency' => 'USD', 'country' => 'US', 'city' => 'New York'],
            ['name' => 'Vega Analytics', 'company_name' => 'Vega Analytics Ltd', 'email' => 'accounts@vega.io', 'currency' => 'USD', 'country' => 'US', 'city' => 'San Francisco'],
            ['name' => 'Cobalt Labs', 'company_name' => 'Cobalt Labs Inc', 'email' => 'finance@cobaltlabs.dev', 'currency' => 'USD', 'country' => 'CA', 'city' => 'Toronto'],
            ['name' => 'Merida Fashion', 'company_name' => 'Merida Fashion House', 'email' => 'invoices@meridafashion.com', 'currency' => 'GBP', 'country' => 'GB', 'city' => 'London'],
            ['name' => 'Apex Builders', 'company_name' => 'Apex Construction Ltd', 'email' => 'ap@apexbuild.co', 'currency' => 'USD', 'country' => 'US', 'city' => 'Austin'],
            ['name' => 'Orion Tech', 'company_name' => 'Orion Technology GmbH', 'email' => 'rechnungen@oriontech.de', 'currency' => 'EUR', 'country' => 'DE', 'city' => 'Berlin'],
        ])->map(fn ($data) => Client::factory()->for($tenant)->create($data));

        $invoiceNum = 1;

        // ── Paid invoices (last 12 months, spread for the revenue chart) ──────
        $paidScenarios = [
            ['client' => 0, 'description' => 'Brand Identity Package', 'qty' => 1, 'price' => 480000, 'months_ago' => 11],
            ['client' => 1, 'description' => 'Analytics Dashboard Design', 'qty' => 1, 'price' => 320000, 'months_ago' => 10],
            ['client' => 2, 'description' => 'Frontend Development Sprint', 'qty' => 80, 'price' => 9500, 'months_ago' => 9],
            ['client' => 0, 'description' => 'Website Redesign', 'qty' => 1, 'price' => 650000, 'months_ago' => 8],
            ['client' => 3, 'description' => 'E-commerce UX Audit', 'qty' => 1, 'price' => 180000, 'months_ago' => 7],
            ['client' => 4, 'description' => 'Marketing Site Build', 'qty' => 1, 'price' => 550000, 'months_ago' => 6],
            ['client' => 1, 'description' => 'Data Visualisation Components', 'qty' => 12, 'price' => 18000, 'months_ago' => 5],
            ['client' => 5, 'description' => 'SaaS Onboarding Flow', 'qty' => 1, 'price' => 280000, 'months_ago' => 4],
            ['client' => 2, 'description' => 'API Integration Work', 'qty' => 40, 'price' => 9500, 'months_ago' => 3],
            ['client' => 0, 'description' => 'Annual Retainer Q1', 'qty' => 1, 'price' => 400000, 'months_ago' => 2],
            ['client' => 3, 'description' => 'Mobile App UI Kit', 'qty' => 1, 'price' => 220000, 'months_ago' => 1],
            ['client' => 4, 'description' => 'Content Strategy Workshop', 'qty' => 2, 'price' => 75000, 'months_ago' => 1],
        ];

        foreach ($paidScenarios as $s) {
            $issueDate = CarbonImmutable::now()->subMonths($s['months_ago'])->startOfMonth()->addDays(rand(0, 10));
            $dueDate = $issueDate->addDays(14);
            $paidAt = $dueDate->subDays(rand(0, 5));

            $subtotal = (int) ($s['qty'] * $s['price']);
            $taxRate = rand(0, 1) ? 10 : 0;
            $taxAmount = (int) round($subtotal * $taxRate / 100);
            $total = $subtotal + $taxAmount;

            $invoice = Invoice::factory()->for($tenant)->for($clients->get($s['client']))->create([
                'number' => sprintf('INV-%d-%04d', now()->year, $invoiceNum++),
                'status' => InvoiceStatus::Paid,
                'tax_rate' => $taxRate,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'amount_paid' => $total,
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'sent_at' => $issueDate,
                'paid_at' => $paidAt,
            ]);

            $invoice->items()->create([
                'description' => $s['description'],
                'quantity' => $s['qty'],
                'unit_price' => $s['price'],
                'position' => 0,
            ]);

            (new Payment)->forceFill([
                'tenant_id' => $tenant->id,
                'invoice_id' => $invoice->id,
                'amount' => $total,
                'currency' => 'USD',
                'provider' => PaymentProvider::Stripe,
                'provider_reference' => 'pi_'.Str::random(24),
                'paid_at' => $paidAt,
            ])->save();
        }

        // ── Sent invoices (awaiting payment) ─────────────────────────────────
        $sentScenarios = [
            ['client' => 1, 'items' => [['Brand Guidelines Update', 1, 150000]], 'days_ago' => 5],
            ['client' => 5, 'items' => [['React Component Library', 1, 380000], ['Setup & CI Config', 1, 45000]], 'days_ago' => 8],
            ['client' => 2, 'items' => [['Monthly Retainer — June', 1, 200000]], 'days_ago' => 2],
        ];

        foreach ($sentScenarios as $s) {
            $issueDate = CarbonImmutable::now()->subDays($s['days_ago']);
            $dueDate = $issueDate->addDays(14);
            $subtotal = (int) collect($s['items'])->sum(fn ($i) => $i[1] * $i[2]);
            $total = $subtotal;

            $invoice = Invoice::factory()->for($tenant)->for($clients->get($s['client']))->create([
                'number' => sprintf('INV-%d-%04d', now()->year, $invoiceNum++),
                'status' => InvoiceStatus::Sent,
                'subtotal' => $subtotal,
                'tax_rate' => 0,
                'tax_amount' => 0,
                'total' => $total,
                'amount_paid' => 0,
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'sent_at' => $issueDate,
            ]);

            foreach ($s['items'] as $pos => $item) {
                $invoice->items()->create([
                    'description' => $item[0],
                    'quantity' => $item[1],
                    'unit_price' => $item[2],
                    'position' => $pos,
                ]);
            }
        }

        // ── Overdue invoices (reminders in various stages) ────────────────────
        $overdueScenarios = [
            ['client' => 0, 'item' => 'Logo & Visual Identity', 'price' => 250000, 'days_overdue' => 3, 'reminders' => []],
            ['client' => 3, 'item' => 'UX Research Report', 'price' => 120000, 'days_overdue' => 8, 'reminders' => [3]],
            ['client' => 4, 'item' => 'Landing Page Design', 'price' => 185000, 'days_overdue' => 16, 'reminders' => [3, 7, 14]],
        ];

        foreach ($overdueScenarios as $s) {
            $dueDate = CarbonImmutable::now()->subDays($s['days_overdue']);
            $issueDate = $dueDate->subDays(14);

            $invoice = Invoice::factory()->for($tenant)->for($clients->get($s['client']))->create([
                'number' => sprintf('INV-%d-%04d', now()->year, $invoiceNum++),
                'status' => InvoiceStatus::Overdue,
                'subtotal' => $s['price'],
                'tax_rate' => 0,
                'tax_amount' => 0,
                'total' => $s['price'],
                'amount_paid' => 0,
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'sent_at' => $issueDate,
                'reminders_sent' => $s['reminders'],
            ]);

            $invoice->items()->create([
                'description' => $s['item'],
                'quantity' => 1,
                'unit_price' => $s['price'],
                'position' => 0,
            ]);
        }

        // ── Draft invoices ────────────────────────────────────────────────────
        $draftScenarios = [
            ['client' => 1, 'item' => 'Q3 Strategy Session', 'price' => 95000],
            ['client' => 5, 'item' => 'Backend API Design', 'price' => 320000],
        ];

        foreach ($draftScenarios as $s) {
            $issueDate = CarbonImmutable::now()->subDays(rand(1, 3));

            $invoice = Invoice::factory()->for($tenant)->for($clients->get($s['client']))->create([
                'number' => sprintf('INV-%d-%04d', now()->year, $invoiceNum++),
                'status' => InvoiceStatus::Draft,
                'subtotal' => $s['price'],
                'tax_rate' => 0,
                'tax_amount' => 0,
                'total' => $s['price'],
                'amount_paid' => 0,
                'issue_date' => $issueDate,
                'due_date' => $issueDate->addDays(14),
            ]);

            $invoice->items()->create([
                'description' => $s['item'],
                'quantity' => 1,
                'unit_price' => $s['price'],
                'position' => 0,
            ]);
        }

        // ── Second tenant (Free plan, isolated data) ──────────────────────────
        $currentTenant->forget();

        $tenant2 = Tenant::factory()->create([
            'name' => 'Pixel & Co.',
            'default_currency' => 'GBP',
            'plan' => TenantPlan::Free,
        ]);

        $currentTenant->set($tenant2);

        User::factory()->owner()->for($tenant2)->create([
            'name' => 'Sam Rivera',
            'email' => 'sam@invoiceflow.test',
            'password' => bcrypt('password'),
        ]);

        $client2 = Client::factory()->for($tenant2)->create([
            'name' => 'Slate Digital',
            'email' => 'accounts@slatedigital.com',
            'currency' => 'GBP',
        ]);

        $t2Scenarios = [
            ['status' => InvoiceStatus::Paid, 'price' => 180000],
            ['status' => InvoiceStatus::Paid, 'price' => 95000],
            ['status' => InvoiceStatus::Sent, 'price' => 130000],
        ];

        foreach ($t2Scenarios as $idx => $s) {
            $issueDate = CarbonImmutable::now()->subDays(rand(5, 30));

            $invoice = Invoice::factory()->for($tenant2)->for($client2)->create([
                'number' => sprintf('PIX-%d-%04d', now()->year, $idx + 1),
                'status' => $s['status'],
                'subtotal' => $s['price'],
                'tax_rate' => 20,
                'tax_amount' => (int) round($s['price'] * 0.20),
                'total' => (int) round($s['price'] * 1.20),
                'amount_paid' => $s['status'] === InvoiceStatus::Paid ? (int) round($s['price'] * 1.20) : 0,
                'issue_date' => $issueDate,
                'due_date' => $issueDate->addDays(30),
                'sent_at' => $issueDate,
                'paid_at' => $s['status'] === InvoiceStatus::Paid ? $issueDate->addDays(rand(5, 20)) : null,
                'currency' => 'GBP',
            ]);

            $invoice->items()->create([
                'description' => 'Graphic Design Services',
                'quantity' => 1,
                'unit_price' => $s['price'],
                'position' => 0,
            ]);
        }

        $this->command->info('Seeded: Northline Studio (Pro, demo@invoiceflow.test / password)');
        $this->command->info('Seeded: Pixel & Co. (Free, sam@invoiceflow.test / password)');
        $this->command->info('API token printed above during seeding — use it in Authorization: Bearer <token>');
    }
}
