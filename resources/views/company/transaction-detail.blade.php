@extends('layouts.company')

@section('title', 'Transaction Detail - Reava Pay')
@section('page-title', 'Transaction Detail')

@push('styles')
<style>
.txn-header { background: linear-gradient(135deg, #0f2027, #203a43, #2c5364); border-radius: 16px; padding: 1.5rem 2rem; color: white; margin-bottom: 1.5rem; }
.txn-ref { font-family: 'SF Mono', Consolas, monospace; font-size: 0.8rem; color: #94a3b8; word-break: break-all; }
.txn-amount { font-size: 2rem; font-weight: 800; color: white; margin: 0.5rem 0; }
.txn-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 1.25rem 1.5rem; margin-bottom: 1rem; }
.txn-card h6 { font-size: 0.82rem; font-weight: 700; color: #1e293b; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.4rem; }
.txn-card h6 i { color: #0ea5e9; }
.txn-row { display: flex; justify-content: space-between; align-items: flex-start; padding: 0.6rem 0; border-bottom: 1px solid #f1f5f9; }
.txn-row:last-child { border-bottom: none; }
.txn-row-label { font-size: 0.78rem; color: #64748b; font-weight: 500; }
.txn-row-value { font-size: 0.82rem; color: #1e293b; font-weight: 600; text-align: right; max-width: 60%; word-break: break-all; }
.txn-row-value.mono { font-family: 'SF Mono', Consolas, monospace; font-size: 0.75rem; color: #475569; }
.badge-status { padding: 0.3rem 0.75rem; border-radius: 8px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; }
.badge-completed { background: rgba(0,200,83,0.1); color: #00c853; }
.badge-failed { background: rgba(255,23,68,0.1); color: #ff1744; }
.badge-processing, .badge-pending { background: rgba(255,145,0,0.1); color: #ff9100; }
.badge-reversed { background: rgba(124,77,255,0.1); color: #7c4dff; }
.badge-channel { padding: 0.25rem 0.6rem; border-radius: 6px; font-size: 0.7rem; font-weight: 600; }
.badge-mpesa { background: rgba(76,175,80,0.1); color: #4caf50; }
.badge-card { background: rgba(33,150,243,0.1); color: #2196f3; }
.badge-bank_transfer { background: rgba(255,152,0,0.1); color: #ff9800; }
.json-block { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1rem; font-family: 'SF Mono', Consolas, monospace; font-size: 0.72rem; color: #334155; white-space: pre-wrap; word-break: break-all; max-height: 300px; overflow-y: auto; }
.timeline { position: relative; padding-left: 1.5rem; }
.timeline::before { content: ''; position: absolute; left: 5px; top: 8px; bottom: 8px; width: 2px; background: #e2e8f0; }
.timeline-item { position: relative; padding: 0.5rem 0; }
.timeline-item::before { content: ''; position: absolute; left: -1.5rem; top: 0.75rem; width: 10px; height: 10px; border-radius: 50%; border: 2px solid #e2e8f0; background: white; z-index: 1; }
.timeline-item.active::before { border-color: #0ea5e9; background: #0ea5e9; }
.timeline-item.success::before { border-color: #00c853; background: #00c853; }
.timeline-item.error::before { border-color: #ff1744; background: #ff1744; }
.timeline-time { font-size: 0.7rem; color: #94a3b8; }
.timeline-label { font-size: 0.8rem; color: #1e293b; font-weight: 600; }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">
    <!-- Breadcrumb -->
    <nav class="mb-3">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('company.reava-pay.settings') }}">Reava Pay</a></li>
            <li class="breadcrumb-item"><a href="{{ route('company.reava-pay.transactions') }}">Transactions</a></li>
            <li class="breadcrumb-item active">{{ $transaction->gwinto_reference }}</li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="txn-header">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge-status badge-{{ $transaction->status }}">{{ ucfirst($transaction->status) }}</span>
                    <span class="badge-channel badge-{{ $transaction->channel }}">
                        <i class="bi {{ $transaction->channel === 'mpesa' ? 'bi-phone' : ($transaction->channel === 'card' ? 'bi-credit-card' : 'bi-bank') }} me-1"></i>
                        {{ $transaction->channel_label }}
                    </span>
                </div>
                <div class="txn-amount">KES {{ number_format($transaction->amount, 2) }}</div>
                <div class="txn-ref">{{ $transaction->gwinto_reference }}</div>
            </div>
            <div class="text-end">
                <div class="small text-white-50">{{ $transaction->type_label }}</div>
                <div class="small text-white-50">{{ $transaction->created_at->format('M d, Y H:i:s') }}</div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-7">
            <!-- Transaction Details -->
            <div class="txn-card">
                <h6><i class="bi bi-receipt"></i> Transaction Details</h6>
                <div class="txn-row">
                    <span class="txn-row-label">Gwinto Reference</span>
                    <span class="txn-row-value mono">{{ $transaction->gwinto_reference }}</span>
                </div>
                <div class="txn-row">
                    <span class="txn-row-label">Reava Pay Reference</span>
                    <span class="txn-row-value mono">{{ $transaction->reava_reference ?? '—' }}</span>
                </div>
                <div class="txn-row">
                    <span class="txn-row-label">Provider Reference</span>
                    <span class="txn-row-value mono">{{ $transaction->provider_reference ?? '—' }}</span>
                </div>
                <div class="txn-row">
                    <span class="txn-row-label">Type</span>
                    <span class="txn-row-value">{{ $transaction->type_label }}</span>
                </div>
                <div class="txn-row">
                    <span class="txn-row-label">Channel</span>
                    <span class="txn-row-value">{{ $transaction->channel_label }}</span>
                </div>
                <div class="txn-row">
                    <span class="txn-row-label">Amount</span>
                    <span class="txn-row-value">{{ $transaction->currency }} {{ number_format($transaction->amount, 2) }}</span>
                </div>
                @if($transaction->charge_amount > 0)
                <div class="txn-row">
                    <span class="txn-row-label">Charge</span>
                    <span class="txn-row-value">{{ $transaction->currency }} {{ number_format($transaction->charge_amount, 2) }}</span>
                </div>
                <div class="txn-row">
                    <span class="txn-row-label">Net Amount</span>
                    <span class="txn-row-value" style="font-weight: 800;">{{ $transaction->currency }} {{ number_format($transaction->net_amount, 2) }}</span>
                </div>
                @endif
                <div class="txn-row">
                    <span class="txn-row-label">Status</span>
                    <span class="txn-row-value"><span class="badge-status badge-{{ $transaction->status }}">{{ ucfirst($transaction->status) }}</span></span>
                </div>
                @if($transaction->failure_reason)
                <div class="txn-row">
                    <span class="txn-row-label">Failure Reason</span>
                    <span class="txn-row-value" style="color: #ff1744;">{{ $transaction->failure_reason }}</span>
                </div>
                @endif
            </div>

            <!-- Payer / Payee Info -->
            <div class="txn-card">
                <h6><i class="bi bi-person"></i> Parties</h6>
                @if($transaction->phone)
                <div class="txn-row">
                    <span class="txn-row-label">Phone</span>
                    <span class="txn-row-value">{{ $transaction->phone }}</span>
                </div>
                @endif
                @if($transaction->email)
                <div class="txn-row">
                    <span class="txn-row-label">Email</span>
                    <span class="txn-row-value">{{ $transaction->email }}</span>
                </div>
                @endif
                @if($transaction->account_reference)
                <div class="txn-row">
                    <span class="txn-row-label">Account Reference</span>
                    <span class="txn-row-value mono">{{ $transaction->account_reference }}</span>
                </div>
                @endif
                @if($transaction->description)
                <div class="txn-row">
                    <span class="txn-row-label">Description</span>
                    <span class="txn-row-value">{{ $transaction->description }}</span>
                </div>
                @endif
            </div>

            <!-- Webhook Payload (if received) -->
            @if($transaction->webhook_payload)
            <div class="txn-card">
                <h6><i class="bi bi-code-square"></i> Webhook Payload</h6>
                <div class="json-block">{{ json_encode($transaction->webhook_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</div>
            </div>
            @endif

            <!-- API Response -->
            @if($transaction->reava_response)
            <div class="txn-card">
                <h6><i class="bi bi-server"></i> API Response</h6>
                <div class="json-block">{{ json_encode($transaction->reava_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</div>
            </div>
            @endif
        </div>

        <!-- Right Column -->
        <div class="col-lg-5">
            <!-- Timeline -->
            <div class="txn-card">
                <h6><i class="bi bi-clock-history"></i> Timeline</h6>
                <div class="timeline">
                    <div class="timeline-item active">
                        <div class="timeline-label">Initiated</div>
                        <div class="timeline-time">{{ $transaction->initiated_at ? $transaction->initiated_at->format('M d, Y H:i:s') : $transaction->created_at->format('M d, Y H:i:s') }}</div>
                    </div>
                    @if($transaction->completed_at)
                    <div class="timeline-item success">
                        <div class="timeline-label">Completed</div>
                        <div class="timeline-time">{{ $transaction->completed_at->format('M d, Y H:i:s') }}</div>
                    </div>
                    @elseif($transaction->failed_at)
                    <div class="timeline-item error">
                        <div class="timeline-label">Failed</div>
                        <div class="timeline-time">{{ $transaction->failed_at->format('M d, Y H:i:s') }}</div>
                    </div>
                    @else
                    <div class="timeline-item">
                        <div class="timeline-label">{{ ucfirst($transaction->status) }}</div>
                        <div class="timeline-time">Awaiting update</div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Linked Records -->
            <div class="txn-card">
                <h6><i class="bi bi-link-45deg"></i> Linked Records</h6>
                @if($transaction->invoice_id)
                <div class="txn-row">
                    <span class="txn-row-label">Invoice</span>
                    <span class="txn-row-value">#{{ $transaction->invoice_id }}</span>
                </div>
                @endif
                @if($transaction->payment_id)
                <div class="txn-row">
                    <span class="txn-row-label">Payment</span>
                    <span class="txn-row-value">#{{ $transaction->payment_id }}</span>
                </div>
                @endif
                @if($transaction->wallet_transaction_id)
                <div class="txn-row">
                    <span class="txn-row-label">Wallet Transaction</span>
                    <span class="txn-row-value">#{{ $transaction->wallet_transaction_id }}</span>
                </div>
                @endif
                @if(!$transaction->invoice_id && !$transaction->payment_id && !$transaction->wallet_transaction_id)
                <p class="text-muted small mb-0">No linked records yet.</p>
                @endif
            </div>

            <!-- Idempotency & Retry -->
            <div class="txn-card">
                <h6><i class="bi bi-shield-check"></i> System Info</h6>
                @if($transaction->idempotency_key)
                <div class="txn-row">
                    <span class="txn-row-label">Idempotency Key</span>
                    <span class="txn-row-value mono">{{ $transaction->idempotency_key }}</span>
                </div>
                @endif
                <div class="txn-row">
                    <span class="txn-row-label">Retry Count</span>
                    <span class="txn-row-value">{{ $transaction->retry_count }}</span>
                </div>
                <div class="txn-row">
                    <span class="txn-row-label">Created</span>
                    <span class="txn-row-value">{{ $transaction->created_at->format('M d, Y H:i:s') }}</span>
                </div>
                <div class="txn-row">
                    <span class="txn-row-label">Last Updated</span>
                    <span class="txn-row-value">{{ $transaction->updated_at->format('M d, Y H:i:s') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Back Button -->
    <div class="mt-3">
        <a href="{{ route('company.reava-pay.transactions') }}" class="btn btn-outline-secondary btn-sm" style="border-radius: 8px;">
            <i class="bi bi-arrow-left me-1"></i> Back to Transactions
        </a>
    </div>
</div>
@endsection
