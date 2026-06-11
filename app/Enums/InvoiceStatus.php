<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvoiceStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::Paid => 'Paid',
            self::Overdue => 'Overdue',
            self::Cancelled => 'Cancelled',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    /**
     * Filament badge color for panel tables and infolists.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::Draft, self::Cancelled => 'gray',
            self::Sent => 'info',
            self::Paid => 'success',
            self::Overdue => 'danger',
        };
    }

    /**
     * Whether an invoice in this status can accept a payment.
     */
    public function isPayable(): bool
    {
        return in_array($this, [self::Sent, self::Overdue], strict: true);
    }

    /**
     * Brand color token for status badges, consistent across the app.
     */
    public function color(): string
    {
        return match ($this) {
            self::Draft, self::Cancelled => 'slate',
            self::Sent => 'lapis',
            self::Paid => 'verdant',
            self::Overdue => 'garnet',
        };
    }
}
