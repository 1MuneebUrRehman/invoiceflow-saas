<?php

namespace App\Models;

use App\Enums\TenantPlan;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $logo_path
 * @property string $default_currency
 * @property TenantPlan $plan
 * @property string|null $stripe_id
 * @property string|null $pm_type
 * @property string|null $pm_last_four
 * @property Carbon|null $trial_ends_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'logo_path', 'default_currency', 'plan', 'stripe_id', 'pm_type', 'pm_last_four', 'trial_ends_at'])]
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'plan' => TenantPlan::class,
            'trial_ends_at' => 'datetime',
        ];
    }

    public function isPro(): bool
    {
        return $this->plan === TenantPlan::Pro;
    }

    /**
     * Currencies offered across the app (clients, invoices, tenant default).
     *
     * @return array<string, string>
     */
    public static function currencyOptions(): array
    {
        return [
            'USD' => 'USD — US Dollar',
            'EUR' => 'EUR — Euro',
            'GBP' => 'GBP — British Pound',
            'CAD' => 'CAD — Canadian Dollar',
            'AUD' => 'AUD — Australian Dollar',
            'NZD' => 'NZD — New Zealand Dollar',
            'CHF' => 'CHF — Swiss Franc',
            'JPY' => 'JPY — Japanese Yen',
            'CNY' => 'CNY — Chinese Yuan',
            'INR' => 'INR — Indian Rupee',
            'PKR' => 'PKR — Pakistani Rupee',
            'BDT' => 'BDT — Bangladeshi Taka',
            'AED' => 'AED — UAE Dirham',
            'SAR' => 'SAR — Saudi Riyal',
            'SGD' => 'SGD — Singapore Dollar',
            'HKD' => 'HKD — Hong Kong Dollar',
            'SEK' => 'SEK — Swedish Krona',
            'NOK' => 'NOK — Norwegian Krone',
            'DKK' => 'DKK — Danish Krone',
            'PLN' => 'PLN — Polish Zloty',
            'TRY' => 'TRY — Turkish Lira',
            'ZAR' => 'ZAR — South African Rand',
            'BRL' => 'BRL — Brazilian Real',
            'MXN' => 'MXN — Mexican Peso',
        ];
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Client, $this>
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
