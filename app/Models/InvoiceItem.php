<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\InvoiceItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $invoice_id
 * @property string $description
 * @property string $quantity
 * @property int $unit_price
 * @property int $amount
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'description',
    'quantity',
    'unit_price',
    'amount',
    'position',
])]
class InvoiceItem extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<InvoiceItemFactory> */
    use HasFactory;

    /**
     * The line amount is always derived from quantity × unit price so it
     * can never drift from its inputs, regardless of where the save originates.
     */
    protected static function booted(): void
    {
        static::saving(function (InvoiceItem $item): void {
            $item->amount = (int) round((float) $item->quantity * $item->unit_price);
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'integer',
            'amount' => 'integer',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
