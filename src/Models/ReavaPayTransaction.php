<?php

namespace Gwinto\ReavaPay\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ReavaPayTransaction extends Model
{
    protected $table = 'reava_pay_transactions';

    protected $fillable = [
        'uuid',
        'company_id',
        'payer_type',
        'payer_id',
        'payee_type',
        'payee_id',
        'type',
        'channel',
        'amount',
        'charge_amount',
        'net_amount',
        'currency',
        'status',
        'reava_reference',
        'provider_reference',
        'gwinto_reference',
        'idempotency_key',
        'phone',
        'email',
        'account_reference',
        'description',
        'authorization_url',
        'callback_url',
        'invoice_id',
        'wallet_transaction_id',
        'payment_id',
        'reava_response',
        'webhook_payload',
        'metadata',
        'failure_reason',
        'retry_count',
        'initiated_at',
        'completed_at',
        'failed_at',
        'expires_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'charge_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'reava_response' => 'array',
        'webhook_payload' => 'array',
        'metadata' => 'array',
        'retry_count' => 'integer',
        'initiated_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REVERSED = 'reversed';
    const STATUS_EXPIRED = 'expired';

    // Type constants
    const TYPE_COLLECTION = 'collection';
    const TYPE_PAYOUT = 'payout';
    const TYPE_WALLET_TOPUP = 'wallet_topup';
    const TYPE_INVOICE_PAYMENT = 'invoice_payment';
    const TYPE_SETTLEMENT = 'settlement';

    // Channel constants
    const CHANNEL_MPESA = 'mpesa';
    const CHANNEL_CARD = 'card';
    const CHANNEL_BANK_TRANSFER = 'bank_transfer';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->gwinto_reference)) {
                $model->gwinto_reference = 'RPG-' . strtoupper(date('Ymd')) . '-' . strtoupper(Str::random(8));
            }
            if (empty($model->initiated_at)) {
                $model->initiated_at = now();
            }
        });
    }

    // Relationships

    public function payer()
    {
        return $this->morphTo();
    }

    public function payee()
    {
        return $this->morphTo();
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function invoice()
    {
        return $this->belongsTo(\App\Models\Invoice::class);
    }

    public function walletTransaction()
    {
        return $this->belongsTo(\App\Models\WalletTransaction::class);
    }

    public function payment()
    {
        return $this->belongsTo(\App\Models\Payment::class);
    }

    // Scopes

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForPayer($query, string $type, int $id)
    {
        return $query->where('payer_type', $type)->where('payer_id', $id);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOfChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Status helpers

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isReversed(): bool
    {
        return $this->status === self::STATUS_REVERSED;
    }

    public function markAsProcessing(): self
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
        return $this;
    }

    public function markAsCompleted(array $data = []): self
    {
        $this->update(array_merge([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ], $data));
        return $this;
    }

    public function markAsFailed(string $reason, array $data = []): self
    {
        $this->update(array_merge([
            'status' => self::STATUS_FAILED,
            'failure_reason' => $reason,
            'failed_at' => now(),
        ], $data));
        return $this;
    }

    // Accessors

    public function getFormattedAmountAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->amount, 2);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'success',
            'pending' => 'warning',
            'processing' => 'info',
            'failed' => 'danger',
            'reversed' => 'secondary',
            'expired' => 'dark',
            default => 'light',
        };
    }

    public function getChannelLabelAttribute(): string
    {
        return match ($this->channel) {
            'mpesa' => 'M-Pesa',
            'card' => 'Card Payment',
            'bank_transfer' => 'Bank Transfer',
            default => ucfirst($this->channel),
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'collection' => 'Collection',
            'payout' => 'Payout',
            'wallet_topup' => 'Wallet Top-Up',
            'invoice_payment' => 'Invoice Payment',
            'settlement' => 'Settlement',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }
}
