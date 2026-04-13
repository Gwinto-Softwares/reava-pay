@extends('layouts.company')

@section('title', 'Reava Pay Settings - ' . $company->name)
@section('page-title', 'Reava Pay Settings')

@push('styles')
<style>
/* ── Reset / Base ─────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; }
.rp-wrap { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.rp-wrap a { text-decoration: none; }

/* ── Hero ─────────────────────────────────────── */
.rp-hero {
    background: linear-gradient(135deg, #0c1445 0%, #1e3a8a 40%, #0369a1 80%, #0e7490 100%);
    border-radius: 20px;
    padding: 2rem;
    color: #fff;
    position: relative;
    overflow: hidden;
    margin-bottom: 1.5rem;
    box-shadow: 0 12px 40px rgba(3, 105, 161, 0.35);
}
.rp-hero::before {
    content: '';
    position: absolute;
    top: -80px; right: -80px;
    width: 300px; height: 300px;
    background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}
.rp-hero::after {
    content: '';
    position: absolute;
    bottom: -60px; left: 30%;
    width: 200px; height: 200px;
    background: radial-gradient(circle, rgba(99,102,241,0.15) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}
.rp-hero-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    position: relative;
    z-index: 1;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
}
.rp-hero-brand { display: flex; align-items: center; gap: 14px; }
.rp-hero-logo {
    width: 52px; height: 52px;
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.25);
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
}
.rp-hero-title { font-size: 1.15rem; font-weight: 800; line-height: 1.2; margin: 0; }
.rp-hero-sub { font-size: 0.8rem; opacity: 0.78; margin-top: 3px; }
.rp-hero-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border-radius: 999px;
    font-size: 0.75rem; font-weight: 700;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.25);
    white-space: nowrap;
}
.rp-hero-badge.on { background: rgba(22,163,74,0.3); }
.rp-hero-badge.off { background: rgba(100,116,139,0.4); }
.rp-hero-badge.warn { background: rgba(220,38,38,0.3); }
.rp-pulse { width: 8px; height: 8px; border-radius: 50%; background: #4ade80; flex-shrink: 0;
    animation: rpPulse 2s ease-in-out infinite; }
@keyframes rpPulse { 0%,100% { box-shadow: 0 0 0 0 rgba(74,222,128,0.5); }
    50% { box-shadow: 0 0 0 5px rgba(74,222,128,0); } }

/* Stats grid */
.rp-hero-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    position: relative; z-index: 1;
}
@media (max-width: 600px) { .rp-hero-stats { grid-template-columns: repeat(2, 1fr); } }
.rp-stat {
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 12px;
    padding: 14px 12px;
    text-align: center;
}
.rp-stat-val { font-size: 1.1rem; font-weight: 800; letter-spacing: -0.01em; line-height: 1; }
.rp-stat-lbl { font-size: 0.68rem; opacity: 0.68; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 5px; }

/* ── Layout ───────────────────────────────────── */
.rp-grid {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 20px;
    align-items: start;
}
@media (max-width: 900px) {
    .rp-grid { grid-template-columns: 1fr; }
    .rp-sidebar { order: -1; }
}
.rp-main-col { display: flex; flex-direction: column; gap: 16px; }
.rp-sidebar { display: flex; flex-direction: column; gap: 14px; position: sticky; top: 16px; }
@media (max-width: 900px) { .rp-sidebar { position: static; } }

/* ── Card ─────────────────────────────────────── */
.rp-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05), 0 4px 16px rgba(0,0,0,0.04);
    transition: box-shadow 0.2s;
}
.rp-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.09); }

.rp-card-head {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    border-bottom: 1px solid #f3f4f6;
    background: #fafbfc;
}
.rp-card-ico {
    width: 38px; height: 38px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}
.rp-card-head-text { flex: 1; min-width: 0; }
.rp-card-head-title { font-size: 0.9rem; font-weight: 700; color: #111827; line-height: 1.3; margin: 0; }
.rp-card-head-desc { font-size: 0.74rem; color: #6b7280; margin-top: 2px; }
.rp-card-body { padding: 20px; }
.rp-card-body-flush { padding: 0; }

/* ── Credentials Display ──────────────────────── */
.rp-cred-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    overflow: hidden;
}
@media (max-width: 560px) { .rp-cred-grid { grid-template-columns: 1fr; } }
.rp-cred-cell {
    padding: 12px 14px;
    border-right: 1px solid #e5e7eb;
    border-bottom: 1px solid #e5e7eb;
    background: #f9fafb;
}
.rp-cred-cell:nth-child(even) { border-right: none; }
.rp-cred-cell.full { grid-column: 1 / -1; border-right: none; }
.rp-cred-cell:last-child,
.rp-cred-cell:nth-last-child(2):not(.full) { border-bottom: none; }
.rp-cred-lbl { font-size: 0.68rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 5px; display: block; }
.rp-cred-row { display: flex; align-items: center; gap: 6px; }
.rp-cred-val { font-size: 0.82rem; color: #111827; font-weight: 500; flex: 1; min-width: 0; word-break: break-all; font-family: 'SF Mono', 'Fira Code', 'Fira Mono', monospace; }
.rp-cred-val.plain { font-family: inherit; font-size: 0.85rem; }
.rp-copy-btn {
    flex-shrink: 0;
    width: 26px; height: 26px;
    border-radius: 6px;
    border: none;
    background: transparent;
    color: #9ca3af;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.15s, color 0.15s;
    padding: 0;
}
.rp-copy-btn:hover { background: #f3f4f6; color: #374151; }
.rp-meta-info {
    display: flex; align-items: flex-start; gap: 8px;
    padding: 10px 14px;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 10px;
    font-size: 0.76rem;
    color: #1e40af;
    line-height: 1.5;
    margin-top: 14px;
}
.rp-meta-info.cyan { background: #ecfeff; border-color: #a5f3fc; color: #0e7490; }
.rp-cred-footer {
    display: flex; align-items: center; gap: 10px;
    flex-wrap: wrap;
    margin-top: 14px;
    padding-top: 14px;
    border-top: 1px solid #f3f4f6;
}
.rp-cred-since { font-size: 0.75rem; color: #6b7280; display: flex; align-items: center; gap: 5px; flex: 1; min-width: 200px; }

/* ── API Form ─────────────────────────────────── */
.rp-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 560px) { .rp-form-row { grid-template-columns: 1fr; } }
.rp-fg { display: flex; flex-direction: column; gap: 4px; }
.rp-fg.full { grid-column: 1 / -1; }
.rp-lbl { font-size: 0.77rem; font-weight: 600; color: #374151; }
.rp-inp {
    padding: 9px 12px;
    border: 1.5px solid #e5e7eb;
    border-radius: 9px;
    font-size: 0.85rem;
    color: #111827;
    background: #fff;
    width: 100%;
    transition: border-color 0.15s, box-shadow 0.15s;
    outline: none;
}
.rp-inp:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
.rp-inp-group { position: relative; display: flex; }
.rp-inp-group .rp-inp { padding-right: 42px; }
.rp-inp-addon {
    position: absolute; right: 0; top: 0; bottom: 0; width: 40px;
    display: flex; align-items: center; justify-content: center;
    background: transparent; border: none; cursor: pointer;
    color: #9ca3af; transition: color 0.15s;
}
.rp-inp-addon:hover { color: #374151; }
.rp-hint { font-size: 0.71rem; color: #9ca3af; }

/* ── Payment Channels — compact rows ─────────── */
.rp-channels { display: flex; flex-direction: column; }
.rp-ch-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    border-bottom: 1px solid #f3f4f6;
    transition: background 0.15s;
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
    user-select: none;
}
.rp-ch-row:last-child { border-bottom: none; }
.rp-ch-row:hover { background: #f9fafb; }
.rp-ch-row.active { background: #f0fdf4; }
.rp-ch-ico {
    width: 40px; height: 40px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.05rem;
    flex-shrink: 0;
}
.rp-ch-info { flex: 1; min-width: 0; }
.rp-ch-name { font-size: 0.88rem; font-weight: 700; color: #111827; }
.rp-ch-sub { font-size: 0.72rem; color: #6b7280; margin-top: 1px; }
.rp-ch-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.rp-ch-status { font-size: 0.68rem; font-weight: 600; letter-spacing: 0.02em; }
.rp-ch-status.on { color: #16a34a; }
.rp-ch-status.off { color: #9ca3af; }

/* ── Toggle switch ───────────────────────────── */
.rp-sw { position: relative; width: 44px; height: 24px; display: inline-block; flex-shrink: 0; }
.rp-sw input { opacity: 0; width: 0; height: 0; position: absolute; }
.rp-sw-track {
    position: absolute; cursor: pointer;
    inset: 0; border-radius: 24px;
    background: #d1d5db;
    transition: background 0.2s;
}
.rp-sw-track::before {
    content: ''; position: absolute;
    width: 18px; height: 18px;
    left: 3px; bottom: 3px;
    background: #fff; border-radius: 50%;
    box-shadow: 0 1px 3px rgba(0,0,0,0.25);
    transition: transform 0.2s;
}
.rp-sw input:checked + .rp-sw-track { background: #16a34a; }
.rp-sw input:checked + .rp-sw-track::before { transform: translateX(20px); }

/* ── Wallet & Settlement ─────────────────────── */
.rp-toggle-rows { display: flex; flex-direction: column; }
.rp-toggle-row {
    display: flex; align-items: center; gap: 14px;
    padding: 14px 0;
    border-bottom: 1px solid #f3f4f6;
}
.rp-toggle-row:last-child { border-bottom: none; }
.rp-toggle-info { flex: 1; min-width: 0; }
.rp-toggle-title { font-size: 0.86rem; font-weight: 600; color: #111827; }
.rp-toggle-desc { font-size: 0.73rem; color: #6b7280; margin-top: 2px; }
.rp-select {
    padding: 9px 12px; border: 1.5px solid #e5e7eb;
    border-radius: 9px; font-size: 0.85rem;
    color: #111827; background: #fff; width: 100%;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 12px center;
    outline: none; cursor: pointer; transition: border-color 0.15s;
}
.rp-select:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }

/* ── Save / Actions bar ──────────────────────── */
.rp-actions {
    display: flex; gap: 10px; flex-wrap: wrap;
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    padding: 14px 16px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    align-items: center;
}
.rp-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 7px;
    padding: 10px 20px; border-radius: 10px;
    font-size: 0.84rem; font-weight: 600;
    cursor: pointer; border: none;
    transition: all 0.15s; white-space: nowrap;
}
.rp-btn:disabled { opacity: 0.55; cursor: not-allowed; }
.rp-btn-primary {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #fff;
    box-shadow: 0 2px 8px rgba(37,99,235,0.35);
    flex: 1;
}
.rp-btn-primary:hover:not(:disabled) { background: linear-gradient(135deg, #1d4ed8, #1e40af); transform: translateY(-1px); box-shadow: 0 4px 14px rgba(37,99,235,0.45); }
.rp-btn-success {
    background: linear-gradient(135deg, #0d9488, #0891b2);
    color: #fff;
    box-shadow: 0 2px 8px rgba(13,148,136,0.35);
    flex: 1;
}
.rp-btn-success:hover:not(:disabled) { background: linear-gradient(135deg, #0f766e, #0781a0); transform: translateY(-1px); }
.rp-btn-outline-danger {
    background: #fff; color: #dc2626;
    border: 1.5px solid #fca5a5;
    flex: 1;
}
.rp-btn-outline-danger:hover:not(:disabled) { background: #fef2f2; border-color: #dc2626; }
.rp-btn-outline-primary {
    background: #fff; color: #2563eb;
    border: 1.5px solid #bfdbfe;
}
.rp-btn-outline-primary:hover:not(:disabled) { background: #eff6ff; border-color: #2563eb; }
@media (max-width: 560px) { .rp-btn { width: 100%; } }

/* ── Sidebar ─────────────────────────────────── */
.rp-sidebar-card { background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
    overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.05); }
.rp-sidebar-head {
    display: flex; align-items: center; gap: 10px;
    padding: 14px 16px;
    border-bottom: 1px solid #f3f4f6;
    background: #fafbfc;
}
.rp-sidebar-head-title { font-size: 0.88rem; font-weight: 700; color: #111827; }
.rp-sidebar-body { padding: 14px 16px; }

/* Quick action buttons */
.rp-qa-btn {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 12px; border-radius: 9px;
    border: 1.5px solid #e5e7eb;
    background: #fff; color: #374151;
    font-size: 0.82rem; font-weight: 500;
    cursor: pointer; transition: all 0.15s;
    text-decoration: none; width: 100%; margin-bottom: 8px;
}
.rp-qa-btn:last-child { margin-bottom: 0; }
.rp-qa-btn:hover { background: #f9fafb; border-color: #9ca3af; color: #111827; }
.rp-qa-ico {
    width: 28px; height: 28px; border-radius: 7px;
    background: #f3f4f6;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; font-size: 0.8rem; color: #374151;
}

/* Meta rows */
.rp-meta-rows { display: flex; flex-direction: column; gap: 8px; padding-top: 10px; }
.rp-meta-row { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.rp-meta-key { font-size: 0.77rem; color: #6b7280; }
.rp-meta-val { font-size: 0.77rem; color: #374151; }
.rp-badge {
    font-size: 0.67rem; font-weight: 700;
    padding: 2px 9px; border-radius: 999px;
    display: inline-block; letter-spacing: 0.02em;
}
.rp-badge-green { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.rp-badge-blue { background: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe; }
.rp-badge-gray { background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb; }
.rp-badge-yellow { background: #fef9c3; color: #854d0e; border: 1px solid #fde047; }
.rp-badge-red { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
.rp-badge-info { background: #cffafe; color: #0e7490; border: 1px solid #a5f3fc; }

/* Status card */
.rp-status-banner {
    display: flex; gap: 12px; align-items: flex-start;
    padding: 14px 16px; border-radius: 12px;
}
.rp-status-banner.ok { background: #f0fdf4; border: 1px solid #bbf7d0; }
.rp-status-banner.warn { background: #fffbeb; border: 1px solid #fde68a; }
.rp-status-banner .ico { flex-shrink: 0; margin-top: 1px; }
.rp-status-banner.ok .ico { color: #16a34a; }
.rp-status-banner.warn .ico { color: #d97706; }
.rp-status-title { font-size: 0.85rem; font-weight: 700; color: #111827; }
.rp-status-desc { font-size: 0.73rem; color: #6b7280; margin-top: 3px; line-height: 1.45; }

/* Webhook */
.rp-webhook-box {
    display: flex; align-items: center; gap: 10px;
    background: #f9fafb; border: 1.5px solid #e5e7eb;
    border-radius: 10px; padding: 10px 12px; flex-wrap: wrap;
}
.rp-webhook-url { font-family: 'SF Mono', 'Fira Code', monospace; font-size: 0.75rem; color: #2563eb; word-break: break-all; flex: 1; min-width: 0; }

/* ── Recent Transactions ─────────────────────── */
.rp-txn-list { display: flex; flex-direction: column; }
.rp-txn-item {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid #f3f4f6;
    transition: background 0.15s;
    text-decoration: none;
    color: inherit;
    cursor: pointer;
}
.rp-txn-item:last-child { border-bottom: none; }
.rp-txn-item:hover { background: #f9fafb; }
.rp-txn-ico {
    width: 34px; height: 34px; border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.85rem; flex-shrink: 0;
}
.rp-txn-ico.mpesa { background: #dcfce7; color: #16a34a; }
.rp-txn-ico.card { background: #dbeafe; color: #2563eb; }
.rp-txn-ico.bank { background: #fef3c7; color: #d97706; }
.rp-txn-ico.other { background: #f3f4f6; color: #6b7280; }
.rp-txn-ref { font-size: 0.8rem; font-weight: 600; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 160px; }
.rp-txn-time { font-size: 0.7rem; color: #9ca3af; margin-top: 1px; }
.rp-txn-right { margin-left: auto; text-align: right; flex-shrink: 0; }
.rp-txn-amt { font-size: 0.82rem; font-weight: 700; color: #111827; white-space: nowrap; }
.rp-txn-badge { font-size: 0.65rem; font-weight: 700; padding: 2px 7px; border-radius: 999px; display: inline-block; margin-top: 3px; }
.rp-txn-badge.completed { background: #dcfce7; color: #15803d; }
.rp-txn-badge.pending { background: #fef9c3; color: #854d0e; }
.rp-txn-badge.failed { background: #fee2e2; color: #dc2626; }
.rp-txn-arrow { color: #d1d5db; font-size: 0.7rem; margin-left: 6px; flex-shrink: 0; }

/* Flash alerts */
.rp-alert {
    display: flex; align-items: center; gap: 8px;
    padding: 12px 16px; border-radius: 10px; font-size: 0.84rem; font-weight: 500;
    margin-bottom: 16px;
}
.rp-alert-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
.rp-alert-error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

/* Env pill */
.rp-env-pill {
    font-size: 0.7rem; font-weight: 700; padding: 3px 10px; border-radius: 999px; letter-spacing: 0.03em;
}
.rp-env-pill.live { background: #dcfce7; color: #15803d; }
.rp-env-pill.test { background: #fef9c3; color: #92400e; }

/* Divider */
.rp-divider { height: 1px; background: #f3f4f6; margin: 12px 0; }

/* Empty state */
.rp-empty { text-align: center; padding: 32px 16px; color: #9ca3af; }
.rp-empty i { font-size: 1.8rem; margin-bottom: 8px; display: block; }
.rp-empty p { font-size: 0.8rem; margin: 0; }
</style>
@endpush

@section('content')
<div class="rp-wrap">

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="rp-alert rp-alert-success">
        <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="rp-alert rp-alert-error">
        <i class="bi bi-exclamation-circle-fill"></i> {{ session('error') }}
    </div>
    @endif

    {{-- ── Hero ── --}}
    <div class="rp-hero">
        <div class="rp-hero-top">
            <div class="rp-hero-brand">
                <div class="rp-hero-logo"><i class="bi bi-shield-lock-fill"></i></div>
                <div>
                    <div class="rp-hero-title">Reava Pay Integration</div>
                    <div class="rp-hero-sub">Accept M-Pesa, Card &amp; Bank payments from your tenants</div>
                </div>
            </div>
            @if(!$platformActive && !$settings->hasValidCredentials())
            <span class="rp-hero-badge warn"><i class="bi bi-exclamation-circle-fill"></i> Not Active</span>
            @elseif($company->reava_pay_enabled ?? $settings->is_active)
            <span class="rp-hero-badge on"><span class="rp-pulse"></span> Enabled</span>
            @else
            <span class="rp-hero-badge off"><i class="bi bi-pause-circle"></i> Disabled</span>
            @endif
        </div>

        <div class="rp-hero-stats">
            <div class="rp-stat">
                <div class="rp-stat-val">{{ number_format($stats['total_transactions']) }}</div>
                <div class="rp-stat-lbl">Transactions</div>
            </div>
            <div class="rp-stat">
                <div class="rp-stat-val">{{ 'KES ' . number_format($stats['completed_amount'], 0) }}</div>
                <div class="rp-stat-lbl">Total Collected</div>
            </div>
            <div class="rp-stat">
                <div class="rp-stat-val">{{ 'KES ' . number_format($stats['this_month'], 0) }}</div>
                <div class="rp-stat-lbl">This Month</div>
            </div>
            <div class="rp-stat">
                <div class="rp-stat-val">{{ $stats['pending_count'] }}</div>
                <div class="rp-stat-lbl">Pending</div>
            </div>
        </div>
    </div>

    {{-- ── Main grid ── --}}
    <div class="rp-grid">

        {{-- ━━ Main column ━━ --}}
        <div class="rp-main-col">

            {{-- ── Credentials Display ── --}}
            @if($credentials)
            <div class="rp-card">
                <div class="rp-card-head" style="background: linear-gradient(to right, #f0f9ff, #eff6ff);">
                    <div class="rp-card-ico" style="background: linear-gradient(135deg, #0ea5e9, #6366f1); color: #fff;">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div class="rp-card-head-text">
                        <div class="rp-card-head-title" style="color: #0369a1;">Reava Pay Credentials</div>
                        <div class="rp-card-head-desc">Your merchant credentials. Use these to access the Reava Pay dashboard.</div>
                    </div>
                    <div class="ms-auto d-flex align-items-center gap-2">
                        <span class="rp-env-pill {{ ($credentials['environment'] ?? '') === 'production' ? 'live' : 'test' }}">
                            {{ ($credentials['environment'] ?? '') === 'production' ? 'Production' : 'Sandbox' }}
                        </span>
                        @if($credentials['is_active'] ?? false)
                        <span class="rp-badge rp-badge-green"><i class="bi bi-check2"></i> Connected</span>
                        @endif
                    </div>
                </div>
                <div class="rp-card-body">
                    <div class="rp-cred-grid">
                        <div class="rp-cred-cell">
                            <span class="rp-cred-lbl">Merchant ID</span>
                            <div class="rp-cred-row">
                                <span class="rp-cred-val">{{ $credentials['merchant_id'] ?? '—' }}</span>
                                @if($credentials['merchant_id'] ?? null)
                                <button type="button" class="rp-copy-btn" onclick="rpCopy('{{ $credentials['merchant_id'] }}', this)" title="Copy"><i class="bi bi-clipboard"></i></button>
                                @endif
                            </div>
                        </div>
                        <div class="rp-cred-cell">
                            <span class="rp-cred-lbl">Login Email</span>
                            <div class="rp-cred-row">
                                <span class="rp-cred-val plain">{{ $credentials['login_email'] ?? $company->email }}</span>
                                <button type="button" class="rp-copy-btn" onclick="rpCopy('{{ $credentials['login_email'] ?? $company->email }}', this)" title="Copy"><i class="bi bi-clipboard"></i></button>
                            </div>
                        </div>
                        @if($credentials['login_password'] ?? null)
                        <div class="rp-cred-cell">
                            <span class="rp-cred-lbl"><i class="bi bi-key me-1"></i>Login Password</span>
                            <div class="rp-cred-row">
                                <span class="rp-cred-val" id="rpLoginPassDisplay">{{ str_repeat('•', max(12, strlen($credentials['login_password']))) }}</span>
                                <button type="button" class="rp-copy-btn" onclick="rpToggleVal('rpLoginPassDisplay', '{{ addslashes($credentials['login_password']) }}', this)" title="Show/Hide"><i class="bi bi-eye"></i></button>
                                <button type="button" class="rp-copy-btn" onclick="rpCopy('{{ addslashes($credentials['login_password']) }}', this)" title="Copy"><i class="bi bi-clipboard"></i></button>
                            </div>
                            <a href="https://reavapay.com/login" target="_blank" style="font-size:0.71rem;color:#2563eb;display:inline-flex;align-items:center;gap:3px;margin-top:5px;">
                                <i class="bi bi-box-arrow-up-right"></i> Sign in at reavapay.com
                            </a>
                        </div>
                        @endif
                        <div class="rp-cred-cell">
                            <span class="rp-cred-lbl">Float Account</span>
                            <div class="rp-cred-row">
                                <span class="rp-cred-val">{{ $credentials['float_account'] ?? 'Pending setup' }}</span>
                                @if($credentials['float_account'] ?? null)
                                <button type="button" class="rp-copy-btn" onclick="rpCopy('{{ $credentials['float_account'] }}', this)" title="Copy"><i class="bi bi-clipboard"></i></button>
                                @endif
                            </div>
                        </div>
                        <div class="rp-cred-cell full">
                            <span class="rp-cred-lbl">API Key</span>
                            <div class="rp-cred-row">
                                <span class="rp-cred-val">{{ $credentials['api_key'] ?? '—' }}</span>
                                @if($credentials['api_key'] ?? null)
                                <button type="button" class="rp-copy-btn" onclick="rpCopy('{{ $credentials['api_key'] }}', this)" title="Copy"><i class="bi bi-clipboard"></i></button>
                                @endif
                            </div>
                        </div>
                        @if($credentials['api_secret'] ?? null)
                        <div class="rp-cred-cell full">
                            <span class="rp-cred-lbl"><i class="bi bi-lock me-1"></i>API Secret</span>
                            <div class="rp-cred-row">
                                <span class="rp-cred-val" id="rpApiSecretDisplay">{{ str_repeat('•', 40) }}</span>
                                <button type="button" class="rp-copy-btn" onclick="rpToggleVal('rpApiSecretDisplay', @json($credentials['api_secret']), this)" title="Show/Hide"><i class="bi bi-eye"></i></button>
                                <button type="button" class="rp-copy-btn" onclick="rpCopy(@json($credentials['api_secret']), this)" title="Copy"><i class="bi bi-clipboard"></i></button>
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="rp-meta-info">
                        <i class="bi bi-info-circle-fill flex-shrink-0" style="margin-top:1px;"></i>
                        <span>Your Gwinto wallet is synced with your Reava Pay float account. All transactions flow bi-directionally in real-time.</span>
                    </div>

                    @if($credentials['connected_at'] ?? null)
                    <div class="rp-cred-footer">
                        <span class="rp-cred-since">
                            <i class="bi bi-clock"></i>
                            Connected since <strong>&nbsp;{{ \Carbon\Carbon::parse($credentials['connected_at'])->format('M d, Y H:i') }}</strong>
                        </span>
                        <form action="{{ route('company.reava-pay.disconnect') }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Disconnect from Reava Pay? You can reconnect at any time.')">
                            @csrf
                            <button type="submit" class="rp-btn rp-btn-outline-danger" style="padding:7px 14px;font-size:0.78rem;">
                                <i class="bi bi-plug"></i> Disconnect
                            </button>
                        </form>
                        <form action="{{ route('company.reava-pay.connect.process') }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="reconnect" value="1">
                            <button type="submit" class="rp-btn rp-btn-outline-primary" style="padding:7px 14px;font-size:0.78rem;">
                                <i class="bi bi-arrow-clockwise"></i> Reconnect
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- ── Settings Form ── --}}
            <form action="{{ route('company.reava-pay.update') }}" method="POST" id="rpSettingsForm">
                @csrf

                {{-- API Credentials --}}
                <div class="rp-card" style="margin-bottom: 16px;">
                    <div class="rp-card-head">
                        <div class="rp-card-ico" style="background: #eef2ff; color: #4f46e5;">
                            <i class="bi bi-key-fill"></i>
                        </div>
                        <div class="rp-card-head-text">
                            <div class="rp-card-head-title">API Credentials</div>
                            <div class="rp-card-head-desc">Your own credentials. Leave blank to use platform credentials.</div>
                        </div>
                    </div>
                    <div class="rp-card-body">
                        @if($platformActive)
                        <div class="rp-meta-info cyan" style="margin-bottom:16px;margin-top:0;">
                            <i class="bi bi-info-circle-fill flex-shrink-0" style="margin-top:1px;"></i>
                            <span>Platform credentials are available. Your own credentials are optional and will take priority if provided.</span>
                        </div>
                        @endif
                        <div class="rp-form-row">
                            <div class="rp-fg">
                                <label class="rp-lbl">API Key</label>
                                <input type="text" class="rp-inp" name="api_key" value="{{ $settings->api_key }}" placeholder="rp_live_••••••••" autocomplete="off">
                            </div>
                            <div class="rp-fg">
                                <label class="rp-lbl">Public Key</label>
                                <input type="text" class="rp-inp" name="public_key" value="{{ $settings->public_key }}" placeholder="rp_pk_••••••••" autocomplete="off">
                            </div>
                            <div class="rp-fg full">
                                <label class="rp-lbl">API Secret</label>
                                <div class="rp-inp-group">
                                    <input type="password" class="rp-inp" id="rpApiSecretInput" name="api_secret"
                                        placeholder="{{ $settings->api_secret_encrypted ? 'Leave blank to keep existing' : 'sk_live_••••••••' }}"
                                        autocomplete="new-password">
                                    <button type="button" class="rp-inp-addon" onclick="rpToggleInput('rpApiSecretInput', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <span class="rp-hint">Leave blank to keep existing secret</span>
                            </div>
                            <div class="rp-fg full">
                                <label class="rp-lbl">Webhook Secret</label>
                                <div class="rp-inp-group">
                                    <input type="password" class="rp-inp" id="rpWebhookSecretInput" name="webhook_secret"
                                        value="{{ $settings->webhook_secret }}"
                                        placeholder="whsec_••••••••"
                                        autocomplete="new-password">
                                    <button type="button" class="rp-inp-addon" onclick="rpToggleInput('rpWebhookSecretInput', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Payment Channels --}}
                <div class="rp-card" style="margin-bottom: 16px;">
                    <div class="rp-card-head">
                        <div class="rp-card-ico" style="background: #f0fdf4; color: #16a34a;">
                            <i class="bi bi-grid-3x3-gap-fill"></i>
                        </div>
                        <div class="rp-card-head-text">
                            <div class="rp-card-head-title">Payment Channels</div>
                            <div class="rp-card-head-desc">Choose which methods your tenants can use to pay</div>
                        </div>
                        <div class="ms-auto">
                            @php $activeCount = (int)$settings->mpesa_enabled + (int)$settings->card_enabled + (int)$settings->bank_transfer_enabled; @endphp
                            <span class="rp-badge {{ $activeCount > 0 ? 'rp-badge-green' : 'rp-badge-gray' }}">{{ $activeCount }}/3 active</span>
                        </div>
                    </div>
                    <div class="rp-channels">
                        {{-- M-Pesa --}}
                        <label class="rp-ch-row {{ $settings->mpesa_enabled ? 'active' : '' }}" style="cursor:pointer;" id="chRowMpesa">
                            <div class="rp-ch-ico mpesa"><i class="bi bi-phone-fill"></i></div>
                            <div class="rp-ch-info">
                                <div class="rp-ch-name">M-Pesa</div>
                                <div class="rp-ch-sub">STK Push &amp; C2B — Safaricom</div>
                            </div>
                            <div class="rp-ch-right">
                                <span class="rp-ch-status {{ $settings->mpesa_enabled ? 'on' : 'off' }}" id="chStatusMpesa">
                                    {{ $settings->mpesa_enabled ? 'Active' : 'Inactive' }}
                                </span>
                                <label class="rp-sw" onclick="event.stopPropagation()">
                                    <input type="checkbox" name="mpesa_enabled" value="1" {{ $settings->mpesa_enabled ? 'checked' : '' }}
                                        onchange="rpChannelToggle(this, 'chRowMpesa', 'chStatusMpesa')">
                                    <span class="rp-sw-track"></span>
                                </label>
                            </div>
                        </label>
                        {{-- Card --}}
                        <label class="rp-ch-row {{ $settings->card_enabled ? 'active' : '' }}" style="cursor:pointer;" id="chRowCard">
                            <div class="rp-ch-ico card"><i class="bi bi-credit-card-fill"></i></div>
                            <div class="rp-ch-info">
                                <div class="rp-ch-name">Card</div>
                                <div class="rp-ch-sub">Visa &amp; Mastercard — Debit &amp; Credit</div>
                            </div>
                            <div class="rp-ch-right">
                                <span class="rp-ch-status {{ $settings->card_enabled ? 'on' : 'off' }}" id="chStatusCard">
                                    {{ $settings->card_enabled ? 'Active' : 'Inactive' }}
                                </span>
                                <label class="rp-sw" onclick="event.stopPropagation()">
                                    <input type="checkbox" name="card_enabled" value="1" {{ $settings->card_enabled ? 'checked' : '' }}
                                        onchange="rpChannelToggle(this, 'chRowCard', 'chStatusCard')">
                                    <span class="rp-sw-track"></span>
                                </label>
                            </div>
                        </label>
                        {{-- Bank Transfer --}}
                        <label class="rp-ch-row {{ $settings->bank_transfer_enabled ? 'active' : '' }}" style="cursor:pointer;" id="chRowBank">
                            <div class="rp-ch-ico bank"><i class="bi bi-bank2"></i></div>
                            <div class="rp-ch-info">
                                <div class="rp-ch-name">Bank Transfer</div>
                                <div class="rp-ch-sub">EFT &amp; RTGS — Direct bank</div>
                            </div>
                            <div class="rp-ch-right">
                                <span class="rp-ch-status {{ $settings->bank_transfer_enabled ? 'on' : 'off' }}" id="chStatusBank">
                                    {{ $settings->bank_transfer_enabled ? 'Active' : 'Inactive' }}
                                </span>
                                <label class="rp-sw" onclick="event.stopPropagation()">
                                    <input type="checkbox" name="bank_transfer_enabled" value="1" {{ $settings->bank_transfer_enabled ? 'checked' : '' }}
                                        onchange="rpChannelToggle(this, 'chRowBank', 'chStatusBank')">
                                    <span class="rp-sw-track"></span>
                                </label>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Wallet & Settlement --}}
                <div class="rp-card" style="margin-bottom: 16px;">
                    <div class="rp-card-head">
                        <div class="rp-card-ico" style="background: #fffbeb; color: #d97706;">
                            <i class="bi bi-wallet2"></i>
                        </div>
                        <div class="rp-card-head-text">
                            <div class="rp-card-head-title">Wallet &amp; Settlement</div>
                            <div class="rp-card-head-desc">Configure how collected funds are managed and settled</div>
                        </div>
                    </div>
                    <div class="rp-card-body">
                        <div class="rp-toggle-rows">
                            <div class="rp-toggle-row">
                                <div class="rp-toggle-info">
                                    <div class="rp-toggle-title">Auto-Credit Wallet</div>
                                    <div class="rp-toggle-desc">Automatically credit your Gwinto wallet upon successful payment</div>
                                </div>
                                <label class="rp-sw">
                                    <input type="checkbox" name="auto_credit_wallet" value="1" {{ $settings->auto_credit_wallet ? 'checked' : '' }}>
                                    <span class="rp-sw-track"></span>
                                </label>
                            </div>
                            <div class="rp-toggle-row">
                                <div class="rp-toggle-info">
                                    <div class="rp-toggle-title">Auto-Settle</div>
                                    <div class="rp-toggle-desc">Automatically settle funds to your linked bank account</div>
                                </div>
                                <label class="rp-sw">
                                    <input type="checkbox" name="auto_settle" value="1" {{ $settings->auto_settle ? 'checked' : '' }}>
                                    <span class="rp-sw-track"></span>
                                </label>
                            </div>
                        </div>
                        <div class="rp-form-row" style="margin-top:16px;">
                            <div class="rp-fg">
                                <label class="rp-lbl">Settlement Schedule</label>
                                <select class="rp-select" name="settlement_schedule">
                                    <option value="">Manual</option>
                                    <option value="daily" {{ $settings->settlement_schedule === 'daily' ? 'selected' : '' }}>Daily</option>
                                    <option value="weekly" {{ $settings->settlement_schedule === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                    <option value="monthly" {{ $settings->settlement_schedule === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                </select>
                            </div>
                            <div class="rp-fg">
                                <label class="rp-lbl">Min Settlement Amount (KES)</label>
                                <input type="number" class="rp-inp" name="min_settlement_amount" value="{{ $settings->min_settlement_amount }}" step="100" min="0" placeholder="1000">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action bar --}}
                <div class="rp-actions">
                    <form action="{{ route('company.reava-pay.toggle') }}" method="POST" style="flex:1;display:flex;">
                        @csrf
                        <button type="submit" class="rp-btn {{ ($company->reava_pay_enabled ?? $settings->is_active) ? 'rp-btn-outline-danger' : 'rp-btn-success' }}" style="width:100%;">
                            @if($company->reava_pay_enabled ?? $settings->is_active)
                                <i class="bi bi-pause-circle"></i> Disable Reava Pay
                            @else
                                <i class="bi bi-play-circle"></i> Enable Reava Pay
                            @endif
                        </button>
                    </form>
                    <button type="submit" form="rpSettingsForm" class="rp-btn rp-btn-primary">
                        <i class="bi bi-check2-circle"></i> Save Settings
                    </button>
                </div>

            </form>
        </div>{{-- /main-col --}}

        {{-- ━━ Sidebar ━━ --}}
        <div class="rp-sidebar">

            {{-- Integration Status --}}
            <div class="rp-status-banner {{ ($company->reava_pay_enabled ?? $settings->is_active) ? 'ok' : 'warn' }}">
                <div class="ico">
                    @if($company->reava_pay_enabled ?? $settings->is_active)
                        <i class="bi bi-check-circle-fill" style="font-size:1.2rem;"></i>
                    @else
                        <i class="bi bi-exclamation-triangle-fill" style="font-size:1.2rem;"></i>
                    @endif
                </div>
                <div>
                    <div class="rp-status-title">{{ ($company->reava_pay_enabled ?? $settings->is_active) ? 'Integration Active' : 'Integration Inactive' }}</div>
                    <div class="rp-status-desc">
                        {{ ($company->reava_pay_enabled ?? $settings->is_active)
                            ? 'Reava Pay is live and processing tenant payments.'
                            : 'Configure credentials and click Enable to start accepting payments.' }}
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="rp-sidebar-card">
                <div class="rp-sidebar-head">
                    <div class="rp-card-ico" style="background:#ede9fe;color:#7c3aed;width:32px;height:32px;border-radius:8px;font-size:0.85rem;">
                        <i class="bi bi-lightning-fill"></i>
                    </div>
                    <div class="rp-sidebar-head-title">Quick Actions</div>
                </div>
                <div class="rp-sidebar-body">
                    <form action="{{ route('company.reava-pay.test-connection') }}" method="POST">
                        @csrf
                        <button type="submit" class="rp-qa-btn" style="border-color:#bfdbfe;">
                            <div class="rp-qa-ico" style="background:#dbeafe;color:#2563eb;"><i class="bi bi-wifi"></i></div>
                            Test Connection
                        </button>
                    </form>
                    <a href="{{ route('company.reava-pay.transactions') }}" class="rp-qa-btn">
                        <div class="rp-qa-ico"><i class="bi bi-list-ul"></i></div>
                        View All Transactions
                    </a>

                    <div class="rp-divider"></div>
                    <div class="rp-meta-rows">
                        <div class="rp-meta-row">
                            <span class="rp-meta-key">Credentials</span>
                            <span class="rp-badge {{ $settings->hasValidCredentials() ? 'rp-badge-blue' : ($platformActive ? 'rp-badge-info' : 'rp-badge-yellow') }}">
                                {{ $settings->hasValidCredentials() ? 'Own' : ($platformActive ? 'Platform' : 'Missing') }}
                            </span>
                        </div>
                        <div class="rp-meta-row">
                            <span class="rp-meta-key">Verified</span>
                            <span class="rp-badge {{ $settings->is_verified ? 'rp-badge-green' : 'rp-badge-yellow' }}">
                                {{ $settings->is_verified ? 'Yes' : 'Pending' }}
                            </span>
                        </div>
                        <div class="rp-meta-row">
                            <span class="rp-meta-key">Environment</span>
                            <span class="rp-badge {{ ($credentials['environment'] ?? '') === 'production' ? 'rp-badge-green' : 'rp-badge-yellow' }}">
                                {{ ($credentials['environment'] ?? '') === 'production' ? 'Production' : 'Sandbox' }}
                            </span>
                        </div>
                        @if($settings->verified_at)
                        <div class="rp-meta-row">
                            <span class="rp-meta-key">Last Verified</span>
                            <span class="rp-meta-val">{{ $settings->verified_at->diffForHumans() }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Webhook URL --}}
            <div class="rp-sidebar-card">
                <div class="rp-sidebar-head">
                    <div class="rp-card-ico" style="background:#fdf2f8;color:#db2777;width:32px;height:32px;border-radius:8px;font-size:0.85rem;">
                        <i class="bi bi-link-45deg"></i>
                    </div>
                    <div class="rp-sidebar-head-title">Webhook URL</div>
                </div>
                <div class="rp-sidebar-body">
                    <p style="font-size:0.75rem;color:#6b7280;margin:0 0 10px;">Set this URL in your Reava Pay dashboard to receive payment notifications:</p>
                    <div class="rp-webhook-box">
                        <span class="rp-webhook-url">{{ url('webhooks/reava-pay') }}</span>
                        <button type="button" class="rp-copy-btn" onclick="rpCopy('{{ url('webhooks/reava-pay') }}', this)" title="Copy URL"
                            style="width:32px;height:32px;background:#fff;border:1px solid #e5e7eb;border-radius:7px;flex-shrink:0;">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Recent Transactions --}}
            <div class="rp-sidebar-card">
                <div class="rp-sidebar-head">
                    <div class="rp-card-ico" style="background:#dbeafe;color:#2563eb;width:32px;height:32px;border-radius:8px;font-size:0.85rem;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="rp-sidebar-head-title" style="flex:1;">Recent Transactions</div>
                    <a href="{{ route('company.reava-pay.transactions') }}" style="font-size:0.72rem;color:#2563eb;font-weight:600;white-space:nowrap;text-decoration:none;">
                        View all <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="rp-txn-list">
                    @forelse($recentTransactions as $txn)
                    @php
                        $chClass = match($txn->channel ?? '') {
                            'mpesa' => 'mpesa',
                            'card'  => 'card',
                            'bank', 'bank_transfer' => 'bank',
                            default => 'other'
                        };
                        $chIcon = match($txn->channel ?? '') {
                            'mpesa' => 'bi-phone-fill',
                            'card'  => 'bi-credit-card-fill',
                            'bank', 'bank_transfer' => 'bi-bank2',
                            default => 'bi-arrow-left-right'
                        };
                        $statusClass = match($txn->status ?? '') {
                            'completed' => 'completed',
                            'pending', 'processing' => 'pending',
                            default => 'failed'
                        };
                    @endphp
                    <a href="{{ route('company.reava-pay.transactions.detail', $txn->id) }}"
                       class="rp-txn-item">
                        <div class="rp-txn-ico {{ $chClass }}">
                            <i class="bi {{ $chIcon }}"></i>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div class="rp-txn-ref">{{ $txn->gwinto_reference }}</div>
                            <div class="rp-txn-time">{{ $txn->created_at->diffForHumans() }}</div>
                        </div>
                        <div class="rp-txn-right">
                            <div class="rp-txn-amt">{{ $txn->formatted_amount }}</div>
                            <div class="rp-txn-badge {{ $statusClass }}">{{ ucfirst($txn->status) }}</div>
                        </div>
                        <i class="bi bi-chevron-right rp-txn-arrow"></i>
                    </a>
                    @empty
                    <div class="rp-empty">
                        <i class="bi bi-inbox"></i>
                        <p>No transactions yet</p>
                    </div>
                    @endforelse
                </div>
            </div>

        </div>{{-- /sidebar --}}
    </div>{{-- /grid --}}

</div>{{-- /rp-wrap --}}
@endsection

@push('scripts')
<script>
// Copy to clipboard
function rpCopy(text, btn) {
    if (!text) return;
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check2 text-success"></i>';
        btn.style.color = '#16a34a';
        setTimeout(() => { btn.innerHTML = orig; btn.style.color = ''; }, 2000);
    }).catch(() => {
        const el = document.createElement('textarea');
        el.value = text;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
    });
}

// Toggle secret value display
const rpToggleState = {};
function rpToggleVal(elId, value, btn) {
    const el = document.getElementById(elId);
    if (!el) return;
    rpToggleState[elId] = !rpToggleState[elId];
    if (rpToggleState[elId]) {
        el.textContent = value;
        btn.innerHTML = '<i class="bi bi-eye-slash"></i>';
    } else {
        el.textContent = '•'.repeat(Math.max(12, (value || '').length));
        btn.innerHTML = '<i class="bi bi-eye"></i>';
    }
}

// Toggle password input visibility
function rpToggleInput(inputId, btn) {
    const inp = document.getElementById(inputId);
    if (!inp) return;
    const isText = inp.type === 'text';
    inp.type = isText ? 'password' : 'text';
    btn.innerHTML = isText ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
}

// Channel toggle: update row highlight + status label
function rpChannelToggle(checkbox, rowId, statusId) {
    const row = document.getElementById(rowId);
    const status = document.getElementById(statusId);
    if (row) row.classList.toggle('active', checkbox.checked);
    if (status) {
        status.textContent = checkbox.checked ? 'Active' : 'Inactive';
        status.className = 'rp-ch-status ' + (checkbox.checked ? 'on' : 'off');
    }
}
</script>
@endpush
