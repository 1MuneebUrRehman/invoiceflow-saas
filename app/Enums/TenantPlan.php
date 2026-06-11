<?php

namespace App\Enums;

enum TenantPlan: string
{
    case Free = 'free';
    case Pro = 'pro';

    /** Maximum invoices per calendar month. Null = unlimited. */
    public function monthlyInvoiceLimit(): ?int
    {
        return match ($this) {
            self::Free => 3,
            self::Pro => null,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Free',
            self::Pro => 'Pro',
        };
    }
}
