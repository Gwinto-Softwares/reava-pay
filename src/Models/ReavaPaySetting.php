<?php

namespace Gwinto\ReavaPay\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ReavaPaySetting extends Model
{
    protected $table = 'reava_pay_settings';

    protected $fillable = [
        'scope_type',
        'scope_id',
        'api_key',
        'public_key',
        'api_secret_encrypted',
        'webhook_secret',
        'base_url',
        'environment',
        'default_currency',
        'mpesa_enabled',
        'card_enabled',
        'bank_transfer_enabled',
        'auto_credit_wallet',
        'auto_settle',
        'settlement_schedule',
        'min_settlement_amount',
        'min_transaction_amount',
        'max_transaction_amount',
        'webhook_url',
        'callback_url',
        'is_active',
        'is_verified',
        'verified_at',
        'last_synced_at',
        'metadata',
    ];

    protected $casts = [
        'mpesa_enabled' => 'boolean',
        'card_enabled' => 'boolean',
        'bank_transfer_enabled' => 'boolean',
        'auto_credit_wallet' => 'boolean',
        'auto_settle' => 'boolean',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'min_settlement_amount' => 'decimal:2',
        'min_transaction_amount' => 'decimal:2',
        'max_transaction_amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'api_secret_encrypted',
        'webhook_secret',
    ];

    /**
     * Get the API secret (decrypted).
     */
    public function getApiSecretAttribute(): ?string
    {
        if (!$this->api_secret_encrypted) {
            return null;
        }

        try {
            return Crypt::decryptString($this->api_secret_encrypted);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set the API secret (encrypted).
     */
    public function setApiSecretAttribute(?string $value): void
    {
        $this->attributes['api_secret_encrypted'] = $value
            ? Crypt::encryptString($value)
            : null;
    }

    /**
     * Get platform-level settings.
     */
    public static function platform(): ?self
    {
        return static::where('scope_type', 'platform')
            ->whereNull('scope_id')
            ->first();
    }

    /**
     * Get or create platform-level settings.
     */
    public static function platformOrCreate(): self
    {
        return static::firstOrCreate(
            ['scope_type' => 'platform', 'scope_id' => null],
            ['base_url' => config('reava-pay.base_url', 'https://reavapay.com/api/v1')]
        );
    }

    /**
     * Get company-level settings.
     */
    public static function forCompany(int $companyId): ?self
    {
        return static::where('scope_type', 'company')
            ->where('scope_id', $companyId)
            ->first();
    }

    /**
     * Get or create company-level settings.
     */
    public static function forCompanyOrCreate(int $companyId): self
    {
        return static::firstOrCreate(
            ['scope_type' => 'company', 'scope_id' => $companyId],
            ['base_url' => config('reava-pay.base_url', 'https://reavapay.com/api/v1')]
        );
    }

    /**
     * Get the effective settings for a company (falls back to platform).
     */
    public static function effectiveForCompany(int $companyId): ?self
    {
        $companySettings = static::forCompany($companyId);

        if ($companySettings && $companySettings->is_active && $companySettings->api_key) {
            return $companySettings;
        }

        return static::platform();
    }

    /**
     * Check if the setting has valid credentials.
     */
    public function hasValidCredentials(): bool
    {
        return !empty($this->api_key) && !empty($this->api_secret_encrypted);
    }

    /**
     * Get enabled channels as array.
     */
    public function getEnabledChannels(): array
    {
        $channels = [];

        if ($this->mpesa_enabled) $channels[] = 'mpesa';
        if ($this->card_enabled) $channels[] = 'card';
        if ($this->bank_transfer_enabled) $channels[] = 'bank_transfer';

        return $channels;
    }

    /**
     * Scope for active settings.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for verified settings.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Relationship to company (when scope is company).
     */
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class, 'scope_id');
    }
}
