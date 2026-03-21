@extends('layouts.admin')

@section('title', 'Reava Pay Transactions - Gwinto Admin')

@push('styles')
<style>
.rp-txn-header {
    background: linear-gradient(135deg, #1e293b 0%, #334155 50%, #0ea5e9 100%);
    border-radius: 20px;
    padding: 2rem 2.5rem;
    color: white;
    position: relative;
    overflow: hidden;
    margin-bottom: 2rem;
}
.rp-txn-header::before {
    content: '';
    position: absolute;
    top: -40%;
    right: -10%;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(14, 165, 233, 0.2) 0%, transparent 70%);
    border-radius: 50%;
}
.stat-pill {
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 12px;
    padding: 0.75rem 1.25rem;
    text-align: center;
}
.stat-pill .value { font-size: 1.5rem; font-weight: 800; }
.stat-pill .label { font-size: 0.7rem; opacity: 0.7; text-transform: uppercase; letter-spacing: 0.5px; }

.filter-bar {
    background: white;
    border-radius: 14px;
    padding: 1rem 1.25rem;
    border: 1px solid #e2e8f0;
    margin-bottom: 1.5rem;
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    align-items: center;
}
.filter-bar .form-select, .filter-bar .form-control {
    border-radius: 10px;
    border: 1.5px solid #e2e8f0;
    font-size: 0.85rem;
    max-width: 180px;
}

.txn-table {
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
}
.txn-table table { margin: 0; }
.txn-table thead th {
    background: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    padding: 0.85rem 1rem;
    font-weight: 700;
}
.txn-table tbody td {
    padding: 0.85rem 1rem;
    vertical-align: middle;
    font-size: 0.85rem;
    border-bottom: 1px solid #f1f5f9;
}
.txn-table tbody tr:hover { background: #f8fafc; }
.channel-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.3rem 0.65rem;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 600;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="rp-txn-header">
        <div class="d-flex justify-content-between align-items-center position-relative" style="z-index: 1;">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-arrow-left-right me-2"></i>Reava Pay Transactions</h3>
                <p class="mb-0 opacity-75">Monitor all Reava Pay payment transactions</p>
            </div>
            <a href="{{ route('admin.reava-pay.settings') }}" class="btn btn-outline-light" style="border-radius: 10px;">
                <i class="bi bi-arrow-left me-1"></i> Back to Settings
            </a>
        </div>
        <div class="d-flex gap-3 mt-3 position-relative" style="z-index: 1;">
            <div class="stat-pill">
                <div class="value">{{ number_format($stats['total']) }}</div>
                <div class="label">Total</div>
            </div>
            <div class="stat-pill">
                <div class="value">{{ number_format($stats['completed']) }}</div>
                <div class="label">Completed</div>
            </div>
            <div class="stat-pill">
                <div class="value">{{ number_format($stats['pending']) }}</div>
                <div class="label">Pending</div>
            </div>
            <div class="stat-pill">
                <div class="value">{{ number_format($stats['failed']) }}</div>
                <div class="label">Failed</div>
            </div>
            <div class="stat-pill">
                <div class="value">KES {{ number_format($stats['volume'], 0) }}</div>
                <div class="label">Volume</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" class="filter-bar">
        <div class="input-group" style="max-width: 250px;">
            <span class="input-group-text" style="border-radius: 10px 0 0 10px;"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Search reference, phone..." style="border-radius: 0 10px 10px 0;">
        </div>
        <select class="form-select" name="status">
            <option value="">All Status</option>
            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
        </select>
        <select class="form-select" name="channel">
            <option value="">All Channels</option>
            <option value="mpesa" {{ request('channel') === 'mpesa' ? 'selected' : '' }}>M-Pesa</option>
            <option value="card" {{ request('channel') === 'card' ? 'selected' : '' }}>Card</option>
            <option value="bank_transfer" {{ request('channel') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
        </select>
        <select class="form-select" name="type">
            <option value="">All Types</option>
            <option value="invoice_payment" {{ request('type') === 'invoice_payment' ? 'selected' : '' }}>Invoice Payment</option>
            <option value="wallet_topup" {{ request('type') === 'wallet_topup' ? 'selected' : '' }}>Wallet Top-Up</option>
            <option value="collection" {{ request('type') === 'collection' ? 'selected' : '' }}>Collection</option>
        </select>
        <button type="submit" class="btn btn-primary" style="border-radius: 10px;"><i class="bi bi-funnel me-1"></i> Filter</button>
        <a href="{{ route('admin.reava-pay.transactions') }}" class="btn btn-outline-secondary" style="border-radius: 10px;">Reset</a>
    </form>

    <!-- Transactions Table -->
    <div class="txn-table">
        <table class="table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Type</th>
                    <th>Channel</th>
                    <th>Company</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $txn)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $txn->gwinto_reference }}</div>
                        @if($txn->reava_reference)
                        <small class="text-muted">{{ Str::limit($txn->reava_reference, 20) }}</small>
                        @endif
                    </td>
                    <td><span class="badge bg-light text-dark" style="border-radius: 8px;">{{ $txn->type_label }}</span></td>
                    <td>
                        <span class="channel-badge" style="background: {{ $txn->channel === 'mpesa' ? '#ecfdf5; color: #059669' : ($txn->channel === 'card' ? '#eff6ff; color: #2563eb' : '#fef3c7; color: #d97706') }};">
                            <i class="bi bi-{{ $txn->channel === 'mpesa' ? 'phone' : ($txn->channel === 'card' ? 'credit-card' : 'bank') }}"></i>
                            {{ $txn->channel_label }}
                        </span>
                    </td>
                    <td>{{ $txn->company?->name ?? '-' }}</td>
                    <td class="fw-bold">{{ $txn->formatted_amount }}</td>
                    <td><span class="badge bg-{{ $txn->status_badge }} rounded-pill">{{ ucfirst($txn->status) }}</span></td>
                    <td>
                        <div>{{ $txn->created_at->format('M d, Y') }}</div>
                        <small class="text-muted">{{ $txn->created_at->format('H:i') }}</small>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-5">
                        <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                        <p class="text-muted mt-2 mb-0">No transactions found</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($transactions->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $transactions->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
