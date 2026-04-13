@extends('layouts.tenant')

@section('title', 'Reava Pay History - Gwinto')
@section('page-title', 'Reava Pay Transactions')

@push('styles')
<style>
.rp-hist-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0ea5e9 100%);
    border-radius: 20px;
    padding: 2rem;
    color: white;
    position: relative;
    overflow: hidden;
    margin-bottom: 2rem;
}
.rp-hist-header::before { content: ''; position: absolute; top: -50%; right: -15%; width: 400px; height: 400px; background: radial-gradient(circle, rgba(14,165,233,0.2) 0%, transparent 70%); border-radius: 50%; }
.hist-stat { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 0.75rem 1.25rem; text-align: center; }
.hist-stat .value { font-size: 1.35rem; font-weight: 800; }
.hist-stat .label { font-size: 0.7rem; opacity: 0.7; text-transform: uppercase; letter-spacing: 0.5px; }

.hist-filters { background: white; border-radius: 14px; padding: 1rem 1.25rem; border: 1px solid #e2e8f0; margin-bottom: 1.5rem; display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center; }
.hist-filters .form-select { border-radius: 10px; border: 1.5px solid #e2e8f0; font-size: 0.85rem; max-width: 160px; }

.hist-card { background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; }
.hist-item { display: flex; align-items: center; padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; transition: background 0.2s; }
.hist-item:hover { background: #f8fafc; }
.hist-item:last-child { border-bottom: none; }
.hist-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-right: 0.75rem; flex-shrink: 0; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="rp-hist-header">
        <div class="d-flex justify-content-between align-items-center position-relative" style="z-index: 1;">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #0ea5e9, #6366f1); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; box-shadow: 0 8px 24px rgba(14,165,233,0.4);">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <div>
                    <h4 class="fw-bold mb-1">Reava Pay Transactions</h4>
                    <p class="mb-0 opacity-75">Your complete payment history</p>
                </div>
            </div>
            <div class="d-flex gap-3">
                <div class="hist-stat">
                    <div class="value">KES {{ number_format($stats['total_paid'], 0) }}</div>
                    <div class="label">Total Paid</div>
                </div>
                <div class="hist-stat">
                    <div class="value">KES {{ number_format($stats['this_month'], 0) }}</div>
                    <div class="label">This Month</div>
                </div>
                <div class="hist-stat">
                    <div class="value">{{ $stats['pending'] }}</div>
                    <div class="label">Pending</div>
                </div>
            </div>
        </div>
    </div>

    <form method="GET" class="hist-filters">
        <select class="form-select" name="status" onchange="this.form.submit()">
            <option value="">All Status</option>
            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
        </select>
        <select class="form-select" name="type" onchange="this.form.submit()">
            <option value="">All Types</option>
            <option value="invoice_payment" {{ request('type') === 'invoice_payment' ? 'selected' : '' }}>Invoice Payment</option>
            <option value="wallet_topup" {{ request('type') === 'wallet_topup' ? 'selected' : '' }}>Wallet Top-Up</option>
        </select>
    </form>

    <div class="hist-card">
        @forelse($transactions as $txn)
        <div class="hist-item">
            <div class="hist-icon" style="background: {{ $txn->status === 'completed' ? '#ecfdf5' : ($txn->status === 'failed' ? '#fef2f2' : '#fefce8') }};">
                <i class="bi bi-{{ $txn->channel === 'mpesa' ? 'phone' : ($txn->channel === 'card' ? 'credit-card' : 'bank') }}"
                   style="color: {{ $txn->status === 'completed' ? '#059669' : ($txn->status === 'failed' ? '#dc2626' : '#d97706') }}; font-size: 1.1rem;"></i>
            </div>
            <div class="flex-grow-1">
                <div class="fw-semibold">{{ $txn->type_label }}</div>
                <small class="text-muted">{{ $txn->gwinto_reference }} &middot; {{ $txn->channel_label }} &middot; {{ $txn->created_at->format('M d, Y H:i') }}</small>
            </div>
            <div class="text-end">
                <div class="fw-bold" style="font-size: 1rem;">{{ $txn->formatted_amount }}</div>
                <span class="badge bg-{{ $txn->status_badge }} rounded-pill" style="font-size: 0.7rem;">{{ ucfirst($txn->status) }}</span>
            </div>
        </div>
        @empty
        <div class="text-center py-5">
            <div style="width: 64px; height: 64px; background: #f1f5f9; border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                <i class="bi bi-inbox text-muted" style="font-size: 1.5rem;"></i>
            </div>
            <h6 class="text-muted">No Transactions Yet</h6>
            <p class="text-muted small">Your Reava Pay transaction history will appear here.</p>
            <a href="{{ route('tenant.reava-pay.wallet-topup') }}" class="btn btn-primary" style="border-radius: 10px; background: linear-gradient(135deg, #0ea5e9, #6366f1); border: none;">
                <i class="bi bi-plus-circle me-1"></i> Top-Up Wallet
            </a>
        </div>
        @endforelse
    </div>

    @if($transactions->hasPages())
    <div class="d-flex justify-content-center mt-4">{{ $transactions->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
