@extends('layouts.company')

@section('title', 'Reava Pay Transactions - ' . $company->name)
@section('page-title', 'Reava Pay Transactions')

@push('styles')
<style>
*, *::before, *::after { box-sizing: border-box; }
.rpt-wrap { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }

/* ── Hero ── */
.rpt-hero {
    background: linear-gradient(135deg, #0c1445 0%, #1e3a8a 40%, #0369a1 80%, #0e7490 100%);
    border-radius: 20px;
    padding: 1.75rem 2rem;
    color: #fff;
    position: relative;
    overflow: hidden;
    margin-bottom: 1.25rem;
    box-shadow: 0 12px 40px rgba(3,105,161,0.3);
}
.rpt-hero::before {
    content: '';
    position: absolute;
    top: -80px; right: -80px;
    width: 280px; height: 280px;
    background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
    border-radius: 50%; pointer-events: none;
}
.rpt-hero-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    position: relative; z-index: 1;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
}
.rpt-hero-title { font-size: 1.3rem; font-weight: 800; margin: 0; }
.rpt-hero-sub { font-size: 0.8rem; opacity: 0.75; margin-top: 3px; }
.rpt-settings-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(8px);
    color: #fff; font-size: 0.82rem; font-weight: 600;
    text-decoration: none; white-space: nowrap;
    transition: background 0.15s;
}
.rpt-settings-btn:hover { background: rgba(255,255,255,0.22); color: #fff; }
.rpt-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    position: relative; z-index: 1;
}
@media (max-width: 600px) { .rpt-stats { grid-template-columns: repeat(2, 1fr); } }
.rpt-stat {
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 12px;
    padding: 13px 12px; text-align: center;
}
.rpt-stat-val { font-size: 1.05rem; font-weight: 800; line-height: 1; letter-spacing: -0.01em; }
.rpt-stat-lbl { font-size: 0.67rem; opacity: 0.68; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 5px; }

/* ── Filter bar ── */
.rpt-filter-bar {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e5e7eb;
    margin-bottom: 16px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    overflow: hidden;
}
.rpt-filter-toggle {
    display: none; /* desktop: hidden */
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    cursor: pointer;
    user-select: none;
    -webkit-tap-highlight-color: transparent;
}
@media (max-width: 768px) {
    .rpt-filter-toggle { display: flex; }
    .rpt-filter-body { display: none; border-top: 1px solid #f3f4f6; }
    .rpt-filter-body.open { display: block; }
}
.rpt-filter-toggle-label { font-size: 0.85rem; font-weight: 700; color: #111827; display: flex; align-items: center; gap: 8px; }
.rpt-filter-toggle-right { display: flex; align-items: center; gap: 8px; }
.rpt-filter-count {
    background: #2563eb; color: #fff;
    font-size: 0.68rem; font-weight: 700;
    width: 18px; height: 18px; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
}
.rpt-filter-chevron { color: #9ca3af; transition: transform 0.2s; font-size: 0.75rem; }
.rpt-filter-chevron.open { transform: rotate(180deg); }
.rpt-filter-inner {
    display: flex; align-items: center; gap: 10px;
    flex-wrap: wrap; padding: 14px 16px;
}
@media (max-width: 768px) { .rpt-filter-inner { flex-direction: column; align-items: stretch; } }
.rpt-search-group { display: flex; align-items: center; flex: 1; min-width: 180px; }
.rpt-search-wrap { position: relative; flex: 1; }
.rpt-search-ico { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 0.8rem; pointer-events: none; }
.rpt-search-inp {
    padding: 8px 12px 8px 32px;
    border: 1.5px solid #e5e7eb;
    border-radius: 9px;
    font-size: 0.84rem;
    color: #111827;
    background: #fff;
    width: 100%;
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.rpt-search-inp:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
.rpt-select {
    padding: 8px 32px 8px 10px;
    border: 1.5px solid #e5e7eb;
    border-radius: 9px;
    font-size: 0.84rem;
    color: #111827;
    background: #fff;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 10px center;
    outline: none; cursor: pointer; min-width: 130px;
    transition: border-color 0.15s;
}
@media (max-width: 768px) { .rpt-select { min-width: 0; width: 100%; }  }
.rpt-select:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
.rpt-filter-actions { display: flex; gap: 8px; }
@media (max-width: 768px) { .rpt-filter-actions { flex-direction: row; } }
.rpt-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    padding: 8px 16px; border-radius: 9px;
    font-size: 0.83rem; font-weight: 600;
    cursor: pointer; border: none;
    transition: all 0.15s; text-decoration: none; white-space: nowrap;
}
.rpt-btn-primary { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; box-shadow: 0 2px 6px rgba(37,99,235,0.3); }
.rpt-btn-primary:hover { background: linear-gradient(135deg, #1d4ed8, #1e40af); transform: translateY(-1px); }
.rpt-btn-outline { background: #fff; color: #374151; border: 1.5px solid #e5e7eb; }
.rpt-btn-outline:hover { background: #f9fafb; border-color: #9ca3af; color: #111827; }

/* ── Active filters ── */
.rpt-active-filters { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; padding: 8px 16px 12px; border-top: 1px solid #f3f4f6; }
.rpt-filter-chip {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 999px;
    background: #eff6ff; color: #1d4ed8;
    border: 1px solid #bfdbfe;
    font-size: 0.72rem; font-weight: 600;
}

/* ── Transaction list (desktop table) ── */
.rpt-table-wrap {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    margin-bottom: 16px;
}
.rpt-table { width: 100%; border-collapse: collapse; }
.rpt-table thead th {
    background: #f9fafb;
    border-bottom: 2px solid #e5e7eb;
    padding: 12px 14px;
    font-size: 0.71rem;
    font-weight: 700;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    white-space: nowrap;
}
.rpt-table tbody td { padding: 13px 14px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; font-size: 0.84rem; }
.rpt-table tbody tr:last-child td { border-bottom: none; }
.rpt-table tbody tr {
    cursor: pointer;
    transition: background 0.12s;
}
.rpt-table tbody tr:hover { background: #f9fafb; }
.rpt-table tbody tr:hover .rpt-row-arrow { opacity: 1; }
.rpt-row-ref { font-weight: 700; color: #111827; font-size: 0.83rem; }
.rpt-row-ref-sub { font-size: 0.71rem; color: #9ca3af; margin-top: 2px; font-family: 'SF Mono', monospace; }
.rpt-row-arrow { opacity: 0; color: #d1d5db; transition: opacity 0.12s; font-size: 0.8rem; }
.rpt-ch-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 8px;
    font-size: 0.74rem; font-weight: 600; white-space: nowrap;
}
.rpt-ch-badge.mpesa { background: #dcfce7; color: #15803d; }
.rpt-ch-badge.card { background: #dbeafe; color: #1d4ed8; }
.rpt-ch-badge.bank { background: #fef3c7; color: #b45309; }
.rpt-ch-badge.other { background: #f3f4f6; color: #6b7280; }
.rpt-status-badge {
    display: inline-block;
    padding: 3px 10px; border-radius: 999px;
    font-size: 0.7rem; font-weight: 700; letter-spacing: 0.02em;
}
.rpt-status-badge.completed { background: #dcfce7; color: #15803d; }
.rpt-status-badge.pending, .rpt-status-badge.processing { background: #fef9c3; color: #854d0e; }
.rpt-status-badge.failed { background: #fee2e2; color: #dc2626; }
.rpt-status-badge.reversed { background: #ede9fe; color: #6d28d9; }
.rpt-type-badge { display: inline-block; padding: 3px 9px; border-radius: 7px; font-size: 0.72rem; font-weight: 600; background: #f3f4f6; color: #374151; }
.rpt-amt { font-weight: 700; color: #111827; white-space: nowrap; }

/* ── Mobile card list ── */
.rpt-card-list { display: none; }
@media (max-width: 768px) {
    .rpt-table-wrap { display: none; }
    .rpt-card-list { display: flex; flex-direction: column; background: #fff; border-radius: 16px; border: 1px solid #e5e7eb; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.05); margin-bottom: 16px; }
}
.rpt-card-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border-bottom: 1px solid #f3f4f6;
    text-decoration: none;
    color: inherit;
    transition: background 0.12s;
    -webkit-tap-highlight-color: transparent;
}
.rpt-card-item:last-child { border-bottom: none; }
.rpt-card-item:hover, .rpt-card-item:active { background: #f9fafb; }
.rpt-card-ico {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.95rem; flex-shrink: 0;
}
.rpt-card-ico.mpesa { background: #dcfce7; color: #16a34a; }
.rpt-card-ico.card { background: #dbeafe; color: #2563eb; }
.rpt-card-ico.bank { background: #fef3c7; color: #d97706; }
.rpt-card-ico.other { background: #f3f4f6; color: #6b7280; }
.rpt-card-body { flex: 1; min-width: 0; }
.rpt-card-ref { font-size: 0.82rem; font-weight: 700; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rpt-card-meta { font-size: 0.71rem; color: #9ca3af; margin-top: 2px; }
.rpt-card-right { text-align: right; flex-shrink: 0; }
.rpt-card-amt { font-size: 0.85rem; font-weight: 700; color: #111827; white-space: nowrap; }
.rpt-card-status { margin-top: 4px; }

/* ── Empty state ── */
.rpt-empty { text-align: center; padding: 48px 20px; }
.rpt-empty-ico { font-size: 2.5rem; color: #d1d5db; margin-bottom: 10px; }
.rpt-empty-title { font-size: 0.9rem; font-weight: 700; color: #374151; margin-bottom: 4px; }
.rpt-empty-sub { font-size: 0.78rem; color: #9ca3af; }

/* ── Pagination ── */
.rpt-pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    flex-wrap: wrap;
    padding: 4px 0 8px;
}
.rpt-pagination .page-link-item {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 36px; height: 36px; padding: 0 10px;
    border-radius: 9px;
    border: 1.5px solid #e5e7eb;
    background: #fff;
    color: #374151;
    font-size: 0.82rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.15s;
    white-space: nowrap;
}
.rpt-pagination .page-link-item:hover { background: #f9fafb; border-color: #9ca3af; color: #111827; }
.rpt-pagination .page-link-item.active { background: #2563eb; border-color: #2563eb; color: #fff; }
.rpt-pagination .page-link-item.disabled { opacity: 0.45; cursor: not-allowed; pointer-events: none; }
.rpt-pagination-wrap { background: #fff; border-radius: 14px; border: 1px solid #e5e7eb; padding: 12px 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.04); }
.rpt-pagination-info { font-size: 0.76rem; color: #6b7280; text-align: center; margin-top: 8px; }
</style>
@endpush

@section('content')
<div class="rpt-wrap">

    @if(session('success'))
    <div style="display:flex;align-items:center;gap:8px;padding:12px 16px;background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;border-radius:10px;font-size:0.84rem;font-weight:500;margin-bottom:14px;">
        <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div style="display:flex;align-items:center;gap:8px;padding:12px 16px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:10px;font-size:0.84rem;font-weight:500;margin-bottom:14px;">
        <i class="bi bi-exclamation-circle-fill"></i> {{ session('error') }}
    </div>
    @endif

    {{-- ── Hero ── --}}
    <div class="rpt-hero">
        <div class="rpt-hero-top">
            <div>
                <div class="rpt-hero-title"><i class="bi bi-arrow-left-right me-2"></i>Reava Pay Transactions</div>
                <div class="rpt-hero-sub">All payment transactions via Reava Pay</div>
            </div>
            <a href="{{ route('company.reava-pay.settings') }}" class="rpt-settings-btn">
                <i class="bi bi-gear-fill"></i> Settings
            </a>
        </div>
        <div class="rpt-stats">
            <div class="rpt-stat">
                <div class="rpt-stat-val">KES {{ number_format($stats['total_volume'], 0) }}</div>
                <div class="rpt-stat-lbl">Total Volume</div>
            </div>
            <div class="rpt-stat">
                <div class="rpt-stat-val">KES {{ number_format($stats['this_month'], 0) }}</div>
                <div class="rpt-stat-lbl">This Month</div>
            </div>
            <div class="rpt-stat">
                <div class="rpt-stat-val">{{ number_format($stats['total_count']) }}</div>
                <div class="rpt-stat-lbl">Transactions</div>
            </div>
            <div class="rpt-stat">
                <div class="rpt-stat-val">{{ $stats['success_rate'] }}%</div>
                <div class="rpt-stat-lbl">Success Rate</div>
            </div>
        </div>
    </div>

    {{-- ── Filters ── --}}
    @php
        $activeFilters = collect([
            request('search') ? 'Search: "' . request('search') . '"' : null,
            request('status') ? 'Status: ' . ucfirst(request('status')) : null,
            request('channel') ? 'Channel: ' . ucfirst(request('channel')) : null,
        ])->filter()->values();
    @endphp
    <form method="GET" action="{{ route('company.reava-pay.transactions') }}" id="rptFilterForm">
        <div class="rpt-filter-bar">
            {{-- Mobile toggle header --}}
            <div class="rpt-filter-toggle" id="rptFilterToggle" onclick="rptToggleFilters()">
                <span class="rpt-filter-toggle-label">
                    <i class="bi bi-funnel"></i>
                    Filters
                    @if($activeFilters->count())
                    <span class="rpt-filter-count">{{ $activeFilters->count() }}</span>
                    @endif
                </span>
                <span class="rpt-filter-toggle-right">
                    @if($activeFilters->count())
                    <span style="font-size:0.72rem;color:#6b7280;">{{ $activeFilters->count() }} active</span>
                    @endif
                    <i class="bi bi-chevron-down rpt-filter-chevron" id="rptFilterChevron"></i>
                </span>
            </div>

            {{-- Filter fields --}}
            <div class="rpt-filter-body {{ $activeFilters->count() ? 'open' : '' }}" id="rptFilterBody">
                <div class="rpt-filter-inner">
                    <div class="rpt-search-wrap" style="flex:1;min-width:180px;">
                        <i class="bi bi-search rpt-search-ico"></i>
                        <input type="text" class="rpt-search-inp" name="search" value="{{ request('search') }}" placeholder="Search reference, phone…">
                    </div>
                    <select class="rpt-select" name="status">
                        <option value="">All Status</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                    <select class="rpt-select" name="channel">
                        <option value="">All Channels</option>
                        <option value="mpesa" {{ request('channel') === 'mpesa' ? 'selected' : '' }}>M-Pesa</option>
                        <option value="card" {{ request('channel') === 'card' ? 'selected' : '' }}>Card</option>
                        <option value="bank_transfer" {{ request('channel') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                    </select>
                    <div class="rpt-filter-actions">
                        <button type="submit" class="rpt-btn rpt-btn-primary">
                            <i class="bi bi-funnel-fill"></i> Apply
                        </button>
                        <a href="{{ route('company.reava-pay.transactions') }}" class="rpt-btn rpt-btn-outline">
                            <i class="bi bi-x"></i> Reset
                        </a>
                    </div>
                </div>

                {{-- Active filter chips --}}
                @if($activeFilters->count())
                <div class="rpt-active-filters">
                    <span style="font-size:0.72rem;color:#6b7280;font-weight:600;">Active:</span>
                    @foreach($activeFilters as $f)
                    <span class="rpt-filter-chip"><i class="bi bi-check2"></i> {{ $f }}</span>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </form>

    {{-- ── Desktop Table ── --}}
    <div class="rpt-table-wrap">
        @if($transactions->count())
        <table class="rpt-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Channel</th>
                    <th>Type</th>
                    <th>Payer</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th style="width:24px;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $txn)
                @php
                    $chClass = match($txn->channel ?? '') { 'mpesa'=>'mpesa','card'=>'card','bank','bank_transfer'=>'bank', default=>'other' };
                    $chIcon = match($txn->channel ?? '') { 'mpesa'=>'bi-phone-fill','card'=>'bi-credit-card-fill','bank','bank_transfer'=>'bi-bank2', default=>'bi-arrow-left-right' };
                    $stClass = match($txn->status ?? '') { 'completed'=>'completed','pending'=>'pending','processing'=>'processing','failed'=>'failed', default=>$txn->status };
                @endphp
                <tr onclick="window.location='{{ route('company.reava-pay.transactions.detail', $txn->id) }}'">
                    <td>
                        <div class="rpt-row-ref">{{ $txn->gwinto_reference }}</div>
                        @if($txn->reava_reference)
                        <div class="rpt-row-ref-sub">{{ Str::limit($txn->reava_reference, 22) }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="rpt-ch-badge {{ $chClass }}">
                            <i class="bi {{ $chIcon }}"></i> {{ $txn->channel_label }}
                        </span>
                    </td>
                    <td><span class="rpt-type-badge">{{ $txn->type_label }}</span></td>
                    <td style="color:#6b7280;font-size:0.8rem;">
                        @if($txn->phone) {{ $txn->phone }}
                        @elseif($txn->email) {{ Str::limit($txn->email, 22) }}
                        @else <span style="color:#d1d5db;">—</span>
                        @endif
                    </td>
                    <td class="rpt-amt">{{ $txn->formatted_amount }}</td>
                    <td><span class="rpt-status-badge {{ $stClass }}">{{ ucfirst($txn->status) }}</span></td>
                    <td style="color:#6b7280;font-size:0.78rem;white-space:nowrap;">
                        <div>{{ $txn->created_at->format('M d, Y') }}</div>
                        <div style="color:#9ca3af;">{{ $txn->created_at->format('H:i') }}</div>
                    </td>
                    <td><i class="bi bi-chevron-right rpt-row-arrow"></i></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="rpt-empty">
            <div class="rpt-empty-ico"><i class="bi bi-inbox"></i></div>
            <div class="rpt-empty-title">No transactions found</div>
            <div class="rpt-empty-sub">Try adjusting your filters or check back later.</div>
        </div>
        @endif
    </div>

    {{-- ── Mobile Card List ── --}}
    <div class="rpt-card-list">
        @if($transactions->count())
            @foreach($transactions as $txn)
            @php
                $chClass = match($txn->channel ?? '') { 'mpesa'=>'mpesa','card'=>'card','bank','bank_transfer'=>'bank', default=>'other' };
                $chIcon = match($txn->channel ?? '') { 'mpesa'=>'bi-phone-fill','card'=>'bi-credit-card-fill','bank','bank_transfer'=>'bi-bank2', default=>'bi-arrow-left-right' };
                $stClass = match($txn->status ?? '') { 'completed'=>'completed','pending'=>'pending','processing'=>'processing','failed'=>'failed', default=>$txn->status };
            @endphp
            <a href="{{ route('company.reava-pay.transactions.detail', $txn->id) }}" class="rpt-card-item">
                <div class="rpt-card-ico {{ $chClass }}">
                    <i class="bi {{ $chIcon }}"></i>
                </div>
                <div class="rpt-card-body">
                    <div class="rpt-card-ref">{{ $txn->gwinto_reference }}</div>
                    <div class="rpt-card-meta">{{ $txn->channel_label }} · {{ $txn->created_at->diffForHumans() }}</div>
                </div>
                <div class="rpt-card-right">
                    <div class="rpt-card-amt">{{ $txn->formatted_amount }}</div>
                    <div class="rpt-card-status">
                        <span class="rpt-status-badge {{ $stClass }}">{{ ucfirst($txn->status) }}</span>
                    </div>
                </div>
                <i class="bi bi-chevron-right" style="color:#d1d5db;font-size:0.78rem;flex-shrink:0;"></i>
            </a>
            @endforeach
        @else
        <div class="rpt-empty">
            <div class="rpt-empty-ico"><i class="bi bi-inbox"></i></div>
            <div class="rpt-empty-title">No transactions found</div>
            <div class="rpt-empty-sub">Try adjusting your filters.</div>
        </div>
        @endif
    </div>

    {{-- ── Pagination ── --}}
    @if($transactions->hasPages())
    <div class="rpt-pagination-wrap">
        <div class="rpt-pagination">
            {{-- Previous --}}
            @if($transactions->onFirstPage())
            <span class="page-link-item disabled"><i class="bi bi-chevron-left"></i> Prev</span>
            @else
            <a href="{{ $transactions->withQueryString()->previousPageUrl() }}" class="page-link-item"><i class="bi bi-chevron-left"></i> Prev</a>
            @endif

            {{-- Page numbers --}}
            @foreach($transactions->withQueryString()->getUrlRange(max(1, $transactions->currentPage() - 2), min($transactions->lastPage(), $transactions->currentPage() + 2)) as $page => $url)
            @if($page == $transactions->currentPage())
            <span class="page-link-item active">{{ $page }}</span>
            @else
            <a href="{{ $url }}" class="page-link-item">{{ $page }}</a>
            @endif
            @endforeach

            {{-- Next --}}
            @if($transactions->hasMorePages())
            <a href="{{ $transactions->withQueryString()->nextPageUrl() }}" class="page-link-item">Next <i class="bi bi-chevron-right"></i></a>
            @else
            <span class="page-link-item disabled">Next <i class="bi bi-chevron-right"></i></span>
            @endif
        </div>
        <div class="rpt-pagination-info">
            Showing {{ $transactions->firstItem() }}–{{ $transactions->lastItem() }} of {{ number_format($transactions->total()) }} transactions
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
// Mobile filter toggle
function rptToggleFilters() {
    const body = document.getElementById('rptFilterBody');
    const chevron = document.getElementById('rptFilterChevron');
    const isOpen = body.classList.toggle('open');
    chevron.classList.toggle('open', isOpen);
}

// Auto-open filters on mobile if active
(function() {
    const hasActive = {{ request()->hasAny(['search','status','channel']) ? 'true' : 'false' }};
    if (hasActive && window.innerWidth <= 768) {
        document.getElementById('rptFilterBody').classList.add('open');
        document.getElementById('rptFilterChevron').classList.add('open');
    }
})();
</script>
@endpush
