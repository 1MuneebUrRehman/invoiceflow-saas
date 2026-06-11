<?php

namespace App\Enums;

enum InvoiceStatus: string
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
