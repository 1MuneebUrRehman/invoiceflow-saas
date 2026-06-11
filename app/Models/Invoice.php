<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Models\Concerns\BelongsToTenant;
use Carbon\CarbonImmutable;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $public_id
 * @property int $tenant_id
 * @property int $client_id
 * @property string $number
 * @property InvoiceStatus $status
 * @property string $currency
 * @property int $subtotal
 * @property string $tax_rate
 * @property int $tax_amount
 * @property int $total
 * @property int $amount_paid
 * @property CarbonImmutable $issue_date
 * @property CarbonImmutable $due_date
 * @property CarbonImmutable|null $sent_at
 * @property CarbonImmutable|null $paid_at
 * @property string|null $notes
 * @property string|null $pdf_path
 * @property array<int, int> $reminders_sent
 * @property CarbonImmutable|null $deleted_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'client_id',
    'number',
    'status',
    'currency',
    'subtotal',
    'tax_rate',
    'tax_amount',
    'total',
    'amount_paid',
    'issue_date',
    'due_date',
    'notes',
    'reminders_sent',
])]
class Invoice extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<InvoiceFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * Generate a ULID for the public link identifier, not the primary key.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'subtotal' => 'integer',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'integer',
            'total' => 'integer',
            'amount_paid' => 'integer',
            'issue_date' => 'date',
            'due_date' => 'date',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
            'reminders_sent' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return HasMany<InvoiceItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('position');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Outstanding balance in minor units.
     */
    public function amountDue(): int
    {
        return max(0, $this->total - $this->amount_paid);
    }

    /**
     * Recompute subtotal, tax and total from the persisted line items.
     *
     * The database is the source of truth — client-side totals shown in
     * forms are previews only and are never persisted directly.
     */
    public function recalculateTotals(): void
    {
        $subtotal = (int) $this->items()->sum('amount');
        $taxAmount = (int) round($subtotal * ((float) $this->tax_rate) / 100);

        $this->forceFill([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $subtotal + $taxAmount,
        ])->saveQuietly();
    }
}
