@extends('layouts.company')

@section('title', 'Transaction · ' . $transaction->gwinto_reference . ' — Reava Pay')
@section('page-title', 'Transaction Detail')

@push('styles')
<style>
*, *::before, *::after { box-sizing: border-box; }
.rpd-wrap { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }

/* ── Breadcrumb ── */
.rpd-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 10px 14px;
    margin-bottom: 16px;
}
.rpd-breadcrumb { display: flex; align-items: center; gap: 2px; flex-wrap: wrap; }
.rpd-bc-item {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 8px; border-radius: 8px;
    text-decoration: none; color: #6b7280;
    font-size: 0.78rem; font-weight: 600;
    transition: all 0.15s; white-space: nowrap;
}
.rpd-bc-item:hover { color: #2563eb; background: #eff6ff; }
.rpd-bc-ico {
    width: 22px; height: 22px; border-radius: 6px;
    background: #f3f4f6;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.7rem; color: #6b7280; flex-shrink: 0;
    transition: all 0.15s;
}
.rpd-bc-item:hover .rpd-bc-ico { background: #dbeafe; color: #2563eb; }
.rpd-bc-active { color: #111827; cursor: default; pointer-events: none; }
.rpd-bc-active .rpd-bc-ico { background: linear-gradient(135deg, #2563eb, #0ea5e9); color: #fff; }
.rpd-bc-sep { color: #d1d5db; font-size: 0.7rem; padding: 0 2px; }
.rpd-bc-ref { max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: inline-block; vertical-align: middle; }
@media (max-width: 480px) { .rpd-bc-ref { max-width: 90px; } }

/* ── Flash alerts ── */
.rpd-alert {
    display: flex; align-items: center; gap: 8px;
    padding: 11px 14px; border-radius: 10px;
    font-size: 0.83rem; font-weight: 500;
    margin-bottom: 14px;
}
.rpd-alert-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
.rpd-alert-error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.rpd-alert-info { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }

/* ── Hero card ── */
.rpd-hero {
    border-radius: 20px;
    padding: 1.75rem 2rem;
    color: #fff;
    position: relative;
    overflow: hidden;
    margin-bottom: 20px;
    box-shadow: 0 12px 40px rgba(0,0,0,0.25);
}
.rpd-hero.completed { background: linear-gradient(135deg, #064e3b 0%, #065f46 40%, #0d9488 100%); }
.rpd-hero.pending, .rpd-hero.processing { background: linear-gradient(135deg, #451a03 0%, #78350f 40%, #b45309 100%); }
.rpd-hero.failed { background: linear-gradient(135deg, #450a0a 0%, #7f1d1d 40%, #b91c1c 100%); }
.rpd-hero.reversed { background: linear-gradient(135deg, #2e1065 0%, #4c1d95 40%, #6d28d9 100%); }
.rpd-hero-default { background: linear-gradient(135deg, #0c1445 0%, #1e3a8a 40%, #0369a1 100%); }
.rpd-hero::before {
    content: '';
    position: absolute; top: -60px; right: -60px;
    width: 220px; height: 220px;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    border-radius: 50%; pointer-events: none;
}
.rpd-hero::after {
    content: '';
    position: absolute; bottom: -50px; left: 40%;
    width: 180px; height: 180px;
    background: radial-gradient(circle, rgba(255,255,255,0.06) 0%, transparent 70%);
    border-radius: 50%; pointer-events: none;
}
.rpd-hero-inner { position: relative; z-index: 1; }
.rpd-hero-badges { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; }
.rpd-hero-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 12px; border-radius: 999px;
    font-size: 0.74rem; font-weight: 700; letter-spacing: 0.02em;
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,0.2);
    background: rgba(255,255,255,0.15);
}
.rpd-amount { font-size: 2.2rem; font-weight: 900; letter-spacing: -0.02em; line-height: 1.1; margin-bottom: 8px; }
.rpd-ref-mono { font-family: 'SF Mono', 'Fira Code', Consolas, monospace; font-size: 0.8rem; opacity: 0.75; word-break: break-all; }
.rpd-hero-meta { display: flex; flex-wrap: wrap; gap: 16px; margin-top: 14px; }
.rpd-hero-meta-item { font-size: 0.78rem; opacity: 0.8; display: flex; align-items: center; gap: 5px; }

/* ── Sync action bar ── */
.rpd-action-bar {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 10px;
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    padding: 12px 16px;
    margin-bottom: 20px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
}
.rpd-action-info { font-size: 0.8rem; color: #6b7280; display: flex; align-items: center; gap: 6px; }
.rpd-action-info strong { color: #111827; }
.rpd-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 7px;
    padding: 9px 18px; border-radius: 9px;
    font-size: 0.83rem; font-weight: 600;
    cursor: pointer; border: none;
    transition: all 0.15s; text-decoration: none; white-space: nowrap;
}
.rpd-btn:disabled { opacity: 0.55; cursor: not-allowed; }
.rpd-btn-sync {
    background: linear-gradient(135deg, #2563eb, #0ea5e9);
    color: #fff;
    box-shadow: 0 2px 8px rgba(37,99,235,0.3);
}
.rpd-btn-sync:hover:not(:disabled) { background: linear-gradient(135deg, #1d4ed8, #0284c7); transform: translateY(-1px); box-shadow: 0 4px 14px rgba(37,99,235,0.4); }
.rpd-btn-back { background: #fff; color: #374151; border: 1.5px solid #e5e7eb; }
.rpd-btn-back:hover { background: #f9fafb; border-color: #9ca3af; color: #111827; }

/* ── Grid layout ── */
.rpd-grid {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 16px;
    align-items: start;
}
@media (max-width: 900px) { .rpd-grid { grid-template-columns: 1fr; } }

/* ── Detail cards ── */
.rpd-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    margin-bottom: 14px;
    transition: box-shadow 0.2s;
}
.rpd-card:hover { box-shadow: 0 4px 18px rgba(0,0,0,0.08); }
.rpd-card:last-child { margin-bottom: 0; }
.rpd-card-head {
    display: flex; align-items: center; gap: 10px;
    padding: 14px 18px;
    border-bottom: 1px solid #f3f4f6;
    background: #fafbfc;
}
.rpd-card-ico {
    width: 32px; height: 32px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.85rem; flex-shrink: 0;
}
.rpd-card-title { font-size: 0.88rem; font-weight: 700; color: #111827; }
.rpd-card-body { padding: 16px 18px; }
.rpd-rows { display: flex; flex-direction: column; }
.rpd-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid #f9fafb;
}
.rpd-row:last-child { border-bottom: none; padding-bottom: 0; }
.rpd-row:first-child { padding-top: 0; }
.rpd-row-lbl { font-size: 0.78rem; color: #6b7280; font-weight: 500; flex-shrink: 0; min-width: 120px; margin-top: 1px; }
.rpd-row-val { font-size: 0.83rem; color: #111827; font-weight: 600; text-align: right; word-break: break-all; flex: 1; }
.rpd-row-val.mono { font-family: 'SF Mono', 'Fira Code', Consolas, monospace; font-size: 0.76rem; color: #374151; }
.rpd-row-val.muted { color: #9ca3af; font-weight: 400; }
.rpd-row-val.big { font-size: 0.95rem; font-weight: 800; color: #111827; }
.rpd-row-val.danger { color: #dc2626; }

/* Status + channel badges */
.rpd-status {
    display: inline-block; padding: 3px 11px; border-radius: 999px;
    font-size: 0.72rem; font-weight: 700; letter-spacing: 0.03em; text-transform: uppercase;
}
.rpd-status.completed { background: #dcfce7; color: #15803d; }
.rpd-status.pending, .rpd-status.processing { background: #fef9c3; color: #854d0e; }
.rpd-status.failed { background: #fee2e2; color: #dc2626; }
.rpd-status.reversed { background: #ede9fe; color: #6d28d9; }
.rpd-channel {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 8px;
    font-size: 0.73rem; font-weight: 600;
}
.rpd-channel.mpesa { background: #dcfce7; color: #15803d; }
.rpd-channel.card { background: #dbeafe; color: #1d4ed8; }
.rpd-channel.bank, .rpd-channel.bank_transfer { background: #fef3c7; color: #b45309; }

/* ── Timeline ── */
.rpd-timeline { padding-left: 20px; position: relative; }
.rpd-timeline::before {
    content: ''; position: absolute;
    left: 7px; top: 12px; bottom: 12px;
    width: 2px; background: #e5e7eb;
}
.rpd-tl-item {
    position: relative;
    padding: 0 0 18px 16px;
}
.rpd-tl-item:last-child { padding-bottom: 0; }
.rpd-tl-dot {
    position: absolute; left: -13px; top: 3px;
    width: 12px; height: 12px; border-radius: 50%;
    border: 2px solid #e5e7eb; background: #fff;
    z-index: 1;
}
.rpd-tl-dot.done { border-color: #16a34a; background: #16a34a; }
.rpd-tl-dot.active { border-color: #2563eb; background: #2563eb;
    box-shadow: 0 0 0 4px rgba(37,99,235,0.15); }
.rpd-tl-dot.fail { border-color: #dc2626; background: #dc2626; }
.rpd-tl-dot.pending { border-color: #d97706; background: #fef9c3; }
.rpd-tl-label { font-size: 0.83rem; font-weight: 700; color: #111827; }
.rpd-tl-time { font-size: 0.71rem; color: #9ca3af; margin-top: 2px; }
.rpd-tl-desc { font-size: 0.75rem; color: #6b7280; margin-top: 3px; }

/* ── JSON block ── */
.rpd-json {
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 12px 14px;
    font-family: 'SF Mono', 'Fira Code', Consolas, monospace;
    font-size: 0.72rem;
    color: #334155;
    white-space: pre-wrap;
    word-break: break-all;
    max-height: 280px;
    overflow-y: auto;
    line-height: 1.5;
}

/* ── Metric highlights ── */
.rpd-metrics {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-top: 14px;
}
@media (max-width: 560px) { .rpd-metrics { grid-template-columns: repeat(2, 1fr); } }
.rpd-metric {
    background: #f9fafb;
    border: 1px solid #f3f4f6;
    border-radius: 10px;
    padding: 12px;
    text-align: center;
}
.rpd-metric-val { font-size: 1rem; font-weight: 800; color: #111827; }
.rpd-metric-lbl { font-size: 0.68rem; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 3px; }

/* ── Copy button ── */
.rpd-copy {
    display: inline-flex; align-items: center; justify-content: center;
    width: 22px; height: 22px; border-radius: 5px;
    border: none; background: transparent;
    color: #9ca3af; cursor: pointer;
    transition: all 0.15s; flex-shrink: 0; vertical-align: middle; margin-left: 4px;
}
.rpd-copy:hover { background: #f3f4f6; color: #374151; }
</style>
@endpush

@section('content')
<div class="rpd-wrap">

    {{-- ── Navigation bar ── --}}
    <div class="rpd-nav">
        <nav class="rpd-breadcrumb">
            <a href="{{ route('company.reava-pay.settings') }}" class="rpd-bc-item">
                <div class="rpd-bc-ico"><i class="bi bi-shield-check"></i></div>
                <span>Reava Pay</span>
            </a>
            <i class="bi bi-chevron-right rpd-bc-sep"></i>
            <a href="{{ route('company.reava-pay.transactions') }}" class="rpd-bc-item">
                <div class="rpd-bc-ico"><i class="bi bi-arrow-left-right"></i></div>
                <span>Transactions</span>
            </a>
            <i class="bi bi-chevron-right rpd-bc-sep"></i>
            <span class="rpd-bc-item rpd-bc-active">
                <div class="rpd-bc-ico"><i class="bi bi-receipt"></i></div>
                <span class="rpd-bc-ref">{{ $transaction->gwinto_reference }}</span>
            </span>
        </nav>
        <a href="{{ route('company.reava-pay.transactions') }}" class="rpd-btn rpd-btn-back" style="padding:7px 14px;font-size:0.78rem;">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="rpd-alert rpd-alert-success"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="rpd-alert rpd-alert-error"><i class="bi bi-exclamation-circle-fill"></i> {{ session('error') }}</div>
    @endif
    @if(session('info'))
    <div class="rpd-alert rpd-alert-info"><i class="bi bi-info-circle-fill"></i> {{ session('info') }}</div>
    @endif

    {{-- ── Hero ── --}}
    @php
        $heroClass = match($transaction->status) {
            'completed' => 'completed',
            'pending', 'processing' => 'pending',
            'failed' => 'failed',
            'reversed' => 'reversed',
            default => 'rpd-hero-default'
        };
        $chClass = match($transaction->channel ?? '') { 'mpesa'=>'mpesa','card'=>'card','bank','bank_transfer'=>'bank', default=>'other' };
        $chIcon = match($transaction->channel ?? '') { 'mpesa'=>'bi-phone-fill','card'=>'bi-credit-card-fill','bank','bank_transfer'=>'bi-bank2', default=>'bi-arrow-left-right' };
    @endphp
    <div class="rpd-hero {{ $heroClass }}">
        <div class="rpd-hero-inner">
            <div class="rpd-hero-badges">
                <span class="rpd-hero-badge">
                    <i class="bi {{ $chIcon }}"></i> {{ $transaction->channel_label }}
                </span>
                <span class="rpd-hero-badge">
                    @if($transaction->status === 'completed') <i class="bi bi-check-circle-fill"></i>
                    @elseif($transaction->status === 'failed') <i class="bi bi-x-circle-fill"></i>
                    @else <i class="bi bi-clock-fill"></i>
                    @endif
                    {{ ucfirst($transaction->status) }}
                </span>
                <span class="rpd-hero-badge">{{ $transaction->type_label }}</span>
            </div>
            <div class="rpd-amount">{{ $transaction->currency }} {{ number_format($transaction->amount, 2) }}</div>
            <div class="rpd-ref-mono">{{ $transaction->gwinto_reference }}</div>
            <div class="rpd-hero-meta">
                <span class="rpd-hero-meta-item"><i class="bi bi-calendar3"></i> {{ $transaction->created_at->format('M d, Y') }}</span>
                <span class="rpd-hero-meta-item"><i class="bi bi-clock"></i> {{ $transaction->created_at->format('H:i:s') }}</span>
                @if($transaction->charge_amount > 0)
                <span class="rpd-hero-meta-item"><i class="bi bi-percent"></i> Fee: {{ $transaction->currency }} {{ number_format($transaction->charge_amount, 2) }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Action bar ── --}}
    <div class="rpd-action-bar">
        <div class="rpd-action-info">
            @if($transaction->reava_reference)
            <i class="bi bi-link-45deg"></i>
            <span>Reava Pay Ref: <strong>{{ Str::limit($transaction->reava_reference, 30) }}</strong></span>
            @else
            <i class="bi bi-info-circle"></i>
            <span>No Reava Pay reference yet — transaction may still be pending.</span>
            @endif
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <form action="{{ route('company.reava-pay.transactions.sync', $transaction->id) }}" method="POST" style="display:inline;">
                @csrf
                <button type="submit" class="rpd-btn rpd-btn-sync"
                    {{ $transaction->isCompleted() && !$transaction->isFailed() ? 'title=Transaction already completed' : '' }}>
                    <i class="bi bi-arrow-repeat"></i>
                    Sync Status
                </button>
            </form>
        </div>
    </div>

    {{-- ── Main grid ── --}}
    <div class="rpd-grid">

        {{-- ── Left column ── --}}
        <div>
            {{-- Transaction Details --}}
            <div class="rpd-card">
                <div class="rpd-card-head">
                    <div class="rpd-card-ico" style="background:#eff6ff;color:#2563eb;"><i class="bi bi-receipt"></i></div>
                    <div class="rpd-card-title">Transaction Details</div>
                </div>
                <div class="rpd-card-body">
                    <div class="rpd-rows">
                        <div class="rpd-row">
                            <span class="rpd-row-lbl">Gwinto Reference</span>
                            <span class="rpd-row-val mono">
                                {{ $transaction->gwinto_reference }}
                                <button type="button" class="rpd-copy" onclick="rpCopy('{{ $transaction->gwinto_reference }}', this)" title="Copy"><i class="bi bi-clipboard"></i></button>
                            </span>
                        </div>
                        <div class="rpd-row">
                            <span class="rpd-row-lbl">Reava Pay Ref</span>
                            @if($transaction->reava_reference)
                            <span class="rpd-row-val mono">
                                {{ $transaction->reava_reference }}
                                <button type="button" class="rpd-copy" onclick="rpCopy('{{ $transaction->reava_reference }}', this)" title="Copy"><i class="bi bi-clipboard"></i></button>
                            </span>
                            @else
                            <span class="rpd-row-val muted">Not assigned yet</span>
                            @endif
                        </div>
                        @if($transaction->provider_reference)
                        <div class="rpd-row">
                            <span class="rpd-row-lbl">Provider Ref</span>
                            <span class="rpd-row-val mono">
                                {{ $transaction->provider_reference }}
                                <button type="button" class="rpd-copy" onclick="rpCopy('{{ $transaction->provider_reference }}', this)" title="Copy"><i class="bi bi-clipboard"></i></button>
                            </span>
                        </div>
                        @endif
                        <div class="rpd-row">
                            <span class="rpd-row-lbl">Channel</span>
                            <span class="rpd-row-val">
                                <span class="rpd-channel {{ $chClass }}">
                                    <i class="bi {{ $chIcon }}"></i> {{ $transaction->channel_label }}
                                </span>
                            </span>
                        </div>
                        <div class="rpd-row">
                            <span class="rpd-row-lbl">Type</span>
                            <span class="rpd-row-val">{{ $transaction->type_label }}</span>
                        </div>
                        <div class="rpd-row">
                            <span class="rpd-row-lbl">Amount</span>
                            <span class="rpd-row-val big">{{ $transaction->currency }} {{ number_format($transaction->amount, 2) }}</span>
                        </div>
                        @if($transaction->charge_amount > 0)
                        <div class="rpd-row">
                            <span class="rpd-row-lbl">Transaction Fee</span>
                            <span class="rpd-row-val" style="color:#d97706;">{{ $transaction->currency }} {{ number_format($transaction->charge_amount, 2) }}</span>
                        </div>
                        <div class="rpd-row">
                            <span class="rpd-row-lbl">Net Amount</span>
                            <span class="rpd-row-val big" style="color:#16a34a;">{{ $transaction->currency }} {{ number_format($transaction->net_amount, 2) }}</span>
                        </div>
                        @endif
                        <div class="rpd-row">
                            <span class="rpd-row-lbl">Status</span>
                            <span class="rpd-row-val"><span class="rpd-status {{ $transaction->status }}">{{ ucfirst($transaction->status) }}</span></span>
                        </div>
                        @if($transaction->failure_reason)
                        <div class="rpd-row">
                            <span class="rpd-row-lbl">Failure Reason</span>
                            <span class="rpd-row-val danger">{{ $transaction->failure_reason }}</span>
                        </div>
                        @endif
                    </div>

                    @if($transaction->charge_amount > 0)
                    <div class="rpd-metrics">
                        <div class="rpd-metric">
                            <div class="rpd-metric-val">{{ $transaction->currency }} {{ number_format($transaction->amount, 2) }}</div>
                            <div class="rpd-metric-lbl">Gross</div>
                        </div>
                        <div class="rpd-metric">
                            <div class="rpd-metric-val" style="color:#d97706;">{{ $transaction->currency }} {{ number_format($transaction->charge_amount, 2) }}</div>
                            <div class="rpd-metric-lbl">Fee</div>
                        </div>
                        <div class="rpd-metric">
                            <div class="rpd-metric-val" style="color:#16a34a;">{{ $transaction->currency }} {{ number_format($transaction->net_amount, 2) }}</div>
                            <div class="rpd-metric-lbl">Net</div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Parties --}}
            <div class="rpd-card">
                <div class="rpd-card-head">
                    <div class="rpd-card-ico" style="background:#f5f3ff;color:#7c3aed;"><i class="bi bi-person-fill"></i></div>
                    <div class="rpd-card-title">Parties</div>
                </div>
                <div class="rpd-card-body">
                    <div class="rpd-rows">
                        @if($transaction->phone)
                        <div class="rpd-row">
                            <span class="rpd-row-lbl">Phone</span>
                            <span class="rpd-row-val">{{ $transaction->phone }}</span>
                        </div>
                        @endif
                        @if($transaction->email)
                        <div class="rpd-row">
                            <span class="rpd-row-lbl">Email</span>
                            <span class="rpd-row-val">{{ $transaction->email }}</span>
                        </div>
                        @endif
                        @if($transaction->account_reference)
                        <div class="rpd-row">
                            <span class="rpd-row-lbl">Account Ref</span>
                            <span class="rpd-row-val mono">{{ $transaction->account_reference }}</span>
                        </div>
                        @endif
                        @if($transaction->description)
                        <div class="rpd-row">
                            <span class="rpd-row-lbl">Description</span>
                            <span class="rpd-row-val">{{ $transaction->description }}</span>
                        </div>
                        @endif
                        @if(!$transaction->phone && !$transaction->email && !$transaction->description)
                        <p style="font-size:0.78rem;color:#9ca3af;margin:0;">No party information available.</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Webhook Payload --}}
            @if($transaction->webhook_payload)
            <div class="rpd-card">
                <div class="rpd-card-head">
                    <div class="rpd-card-ico" style="background:#ecfdf5;color:#059669;"><i class="bi bi-code-square"></i></div>
                    <div class="rpd-card-title">Webhook Payload</div>
                </div>
                <div class="rpd-card-body" style="padding-top:12px;">
                    <div class="rpd-json">{{ json_encode($transaction->webhook_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</div>
                </div>
            </div>
            @endif

            {{-- API Response --}}
            @if($transaction->reava_response)
            <div class="rpd-card">
                <div class="rpd-card-head">
                    <div class="rpd-card-ico" style="background:#fdf2f8;color:#db2777;"><i class="bi bi-server"></i></div>
                    <div class="rpd-card-title">API Response</div>
                </div>
                <div class="rpd-card-body" style="padding-top:12px;">
                    <div class="rpd-json">{{ json_encode($transaction->reava_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</div>
                </div>
            </div>
            @endif
        </div>

        {{-- ── Right column ── --}}
        <div>

            {{-- Timeline --}}
            <div class="rpd-card">
                <div class="rpd-card-head">
                    <div class="rpd-card-ico" style="background:#eff6ff;color:#2563eb;"><i class="bi bi-clock-history"></i></div>
                    <div class="rpd-card-title">Timeline</div>
                </div>
                <div class="rpd-card-body">
                    <div class="rpd-timeline">
                        <div class="rpd-tl-item">
                            <div class="rpd-tl-dot done"></div>
                            <div class="rpd-tl-label">Initiated</div>
                            <div class="rpd-tl-time">{{ ($transaction->initiated_at ?? $transaction->created_at)->format('M d, Y H:i:s') }}</div>
                            <div class="rpd-tl-desc">Transaction created in Gwinto</div>
                        </div>
                        @if($transaction->status === 'processing' || $transaction->completed_at || $transaction->failed_at)
                        <div class="rpd-tl-item">
                            <div class="rpd-tl-dot {{ $transaction->completed_at || $transaction->failed_at ? 'done' : 'active' }}"></div>
                            <div class="rpd-tl-label">Processing</div>
                            <div class="rpd-tl-time">Sent to {{ $transaction->channel_label }}</div>
                        </div>
                        @endif
                        @if($transaction->completed_at)
                        <div class="rpd-tl-item">
                            <div class="rpd-tl-dot done"></div>
                            <div class="rpd-tl-label" style="color:#16a34a;">Completed</div>
                            <div class="rpd-tl-time">{{ $transaction->completed_at->format('M d, Y H:i:s') }}</div>
                            <div class="rpd-tl-desc">Payment received successfully</div>
                        </div>
                        @elseif($transaction->failed_at)
                        <div class="rpd-tl-item">
                            <div class="rpd-tl-dot fail"></div>
                            <div class="rpd-tl-label" style="color:#dc2626;">Failed</div>
                            <div class="rpd-tl-time">{{ $transaction->failed_at->format('M d, Y H:i:s') }}</div>
                            @if($transaction->failure_reason)
                            <div class="rpd-tl-desc" style="color:#dc2626;">{{ $transaction->failure_reason }}</div>
                            @endif
                        </div>
                        @elseif(in_array($transaction->status, ['pending', 'processing']))
                        <div class="rpd-tl-item">
                            <div class="rpd-tl-dot pending"></div>
                            <div class="rpd-tl-label" style="color:#d97706;">Awaiting Confirmation</div>
                            <div class="rpd-tl-time">Use "Sync Status" to check latest state</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Sync Status Card --}}
            <div class="rpd-card" style="background:linear-gradient(135deg,#eff6ff,#fff);border-color:#bfdbfe;">
                <div class="rpd-card-head" style="background:transparent;border-color:#dbeafe;">
                    <div class="rpd-card-ico" style="background:#dbeafe;color:#1d4ed8;"><i class="bi bi-arrow-repeat"></i></div>
                    <div class="rpd-card-title" style="color:#1d4ed8;">Sync with Reava Pay</div>
                </div>
                <div class="rpd-card-body">
                    <p style="font-size:0.78rem;color:#4b5563;line-height:1.55;margin:0 0 14px;">
                        Pull the latest status from Reava Pay. Use this if the transaction appears stuck or the status hasn't updated after a payment was made.
                    </p>
                    @if($transaction->reava_reference)
                    <form action="{{ route('company.reava-pay.transactions.sync', $transaction->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="rpd-btn rpd-btn-sync" style="width:100%;">
                            <i class="bi bi-arrow-repeat"></i> Sync Status Now
                        </button>
                    </form>
                    @else
                    <div style="background:#fef9c3;border:1px solid #fde047;border-radius:9px;padding:10px 12px;font-size:0.76rem;color:#854d0e;display:flex;align-items:flex-start;gap:8px;">
                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0" style="margin-top:1px;"></i>
                        <span>This transaction has no Reava Pay reference yet. It may not have been submitted yet or is still initialising.</span>
                    </div>
                    @endif
                    @if($transaction->updated_at)
                    <p style="font-size:0.7rem;color:#9ca3af;margin:10px 0 0;text-align:center;">
                        Last updated {{ $transaction->updated_at->diffForHumans() }}
                    </p>
                    @endif
                </div>
            </div>

            {{-- Linked Records --}}
            @if($transaction->invoice_id || $transaction->payment_id || $transaction->wallet_transaction_id)
            <div class="rpd-card">
                <div class="rpd-card-head">
                    <div class="rpd-card-ico" style="background:#fdf2f8;color:#db2777;"><i class="bi bi-link-45deg"></i></div>
                    <div class="rpd-card-title">Linked Records</div>
                </div>
                <div class="rpd-card-body">
                    <div class="rpd-rows">
                        @if($transaction->invoice_id)
                        <div class="rpd-row">
                            <span class="rpd-row-lbl">Invoice</span>
                            <span class="rpd-row-val">#{{ $transaction->invoice_id }}</span>
                        </div>
                        @endif
                        @if($transaction->payment_id)
                        <div class="rpd-row">
                            <span class="rpd-row-lbl">Payment</span>
                            <span class="rpd-row-val">#{{ $transaction->payment_id }}</span>
                        </div>
                        @endif
                        @if($transaction->wallet_transaction_id)
                        <div class="rpd-row">
                            <span class="rpd-row-lbl">Wallet Txn</span>
                            <span class="rpd-row-val">#{{ $transaction->wallet_transaction_id }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- System Info --}}
            <div class="rpd-card">
                <div class="rpd-card-head">
                    <div class="rpd-card-ico" style="background:#f0fdf4;color:#16a34a;"><i class="bi bi-shield-check"></i></div>
                    <div class="rpd-card-title">System Info</div>
                </div>
                <div class="rpd-card-body">
                    <div class="rpd-rows">
                        @if($transaction->idempotency_key)
                        <div class="rpd-row">
                            <span class="rpd-row-lbl">Idempotency Key</span>
                            <span class="rpd-row-val mono" style="font-size:0.71rem;">{{ Str::limit($transaction->idempotency_key, 26) }}</span>
                        </div>
                        @endif
                        <div class="rpd-row">
                            <span class="rpd-row-lbl">Retry Count</span>
                            <span class="rpd-row-val">
                                {{ $transaction->retry_count ?? 0 }}
                                @if(($transaction->retry_count ?? 0) > 0)
                                <span style="background:#fef9c3;color:#854d0e;font-size:0.65rem;font-weight:700;padding:1px 6px;border-radius:5px;margin-left:4px;">retried</span>
                                @endif
                            </span>
                        </div>
                        <div class="rpd-row">
                            <span class="rpd-row-lbl">Created</span>
                            <span class="rpd-row-val" style="font-size:0.78rem;">{{ $transaction->created_at->format('M d, Y H:i:s') }}</span>
                        </div>
                        <div class="rpd-row">
                            <span class="rpd-row-lbl">Updated</span>
                            <span class="rpd-row-val" style="font-size:0.78rem;">{{ $transaction->updated_at->format('M d, Y H:i:s') }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- /right --}}
    </div>{{-- /grid --}}

</div>{{-- /rpd-wrap --}}
@endsection

@push('scripts')
<script>
function rpCopy(text, btn) {
    if (!text) return;
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check2" style="color:#16a34a"></i>';
        setTimeout(() => { btn.innerHTML = orig; }, 2000);
    });
}
</script>
@endpush
