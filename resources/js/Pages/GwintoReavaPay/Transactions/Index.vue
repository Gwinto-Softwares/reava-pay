<script setup>
import { ref, computed, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';

const props = defineProps({
    transactions: { type: Array, default: () => [] },
    pagination: { type: Object, default: () => ({}) },
    summary: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

// ── Filters ──
const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const channel = ref(props.filters.channel ?? '');
const type = ref(props.filters.type ?? '');

const hasActiveFilters = computed(() =>
    search.value || status.value || channel.value || type.value
);

let searchTimer = null;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => applyFilters(), 400);
});

const applyFilters = () => {
    router.get(route('gwinto.reava-pay.transactions.index'), {
        search: search.value || undefined,
        status: status.value || undefined,
        channel: channel.value || undefined,
        type: type.value || undefined,
    }, { preserveState: true, preserveScroll: true });
};

const resetFilters = () => {
    search.value = '';
    status.value = '';
    channel.value = '';
    type.value = '';
    router.get(route('gwinto.reava-pay.transactions.index'), {}, { preserveState: true });
};

const goToPage = (page) => {
    router.get(route('gwinto.reava-pay.transactions.index'), {
        page,
        search: search.value || undefined,
        status: status.value || undefined,
        channel: channel.value || undefined,
        type: type.value || undefined,
    }, { preserveScroll: true });
};

// ── Formatters ──
const fmt = (amount, currency = 'KES') =>
    new Intl.NumberFormat('en-KE', { style: 'currency', currency, minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount ?? 0);

const fmtCompact = (n) => {
    const num = Number(n);
    if (num >= 1_000_000) return (num / 1_000_000).toFixed(1) + 'M';
    if (num >= 1_000) return (num / 1_000).toFixed(1) + 'K';
    return num.toLocaleString();
};

const fmtDate = (d) => {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-KE', { day: '2-digit', month: 'short', year: 'numeric' });
};

const fmtTime = (d) => {
    if (!d) return '';
    return new Date(d).toLocaleTimeString('en-KE', { hour: '2-digit', minute: '2-digit' });
};

const truncRef = (ref, max = 22) => {
    if (!ref) return '—';
    return ref.length > max ? ref.slice(0, max) + '…' : ref;
};

// ── Status helpers ──
const statusConfig = {
    completed: { label: 'Completed', cls: 'status--success' },
    success: { label: 'Completed', cls: 'status--success' },
    pending: { label: 'Pending', cls: 'status--pending' },
    processing: { label: 'Processing', cls: 'status--processing' },
    failed: { label: 'Failed', cls: 'status--failed' },
    reversed: { label: 'Reversed', cls: 'status--reversed' },
    cancelled: { label: 'Cancelled', cls: 'status--cancelled' },
};
const getStatus = (s) => statusConfig[s?.toLowerCase()] ?? { label: s ?? 'Unknown', cls: 'status--default' };

// ── Channel helpers ──
const channelConfig = {
    mpesa: { label: 'M-Pesa', icon: '📱', cls: 'ch--mpesa' },
    m_pesa: { label: 'M-Pesa', icon: '📱', cls: 'ch--mpesa' },
    card: { label: 'Card', icon: '💳', cls: 'ch--card' },
    bank_transfer: { label: 'Bank', icon: '🏦', cls: 'ch--bank' },
};
const getChannel = (c) => channelConfig[c?.toLowerCase()] ?? { label: c ?? '—', icon: '💱', cls: 'ch--default' };

const typeIcon = (t) => t?.toLowerCase() === 'payout' ? '↑' : '↓';
const typeClass = (t) => t?.toLowerCase() === 'payout' ? 'type--payout' : 'type--collection';

const pages = computed(() => {
    const total = props.pagination.last_page ?? 1;
    const cur = props.pagination.current_page ?? 1;
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
    const arr = [1];
    if (cur > 3) arr.push('…');
    for (let i = Math.max(2, cur - 1); i <= Math.min(total - 1, cur + 1); i++) arr.push(i);
    if (cur < total - 2) arr.push('…');
    arr.push(total);
    return arr;
});
</script>

<template>
    <div class="txn-page">

        <!-- ── Hero ── -->
        <div class="txn-hero">
            <div class="txn-hero-top">
                <div class="txn-hero-brand">
                    <div class="txn-hero-icon">
                        <svg width="22" height="22" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                    </div>
                    <div>
                        <div class="txn-hero-title">Reava Pay Transactions</div>
                        <div class="txn-hero-sub">All payment transactions via Reava Pay</div>
                    </div>
                </div>
                <Link :href="route('gwinto.reava-pay.settings')" class="txn-settings-btn">
                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>
                    Settings
                </Link>
            </div>

            <!-- Stats -->
            <div class="txn-stats">
                <div class="txn-stat">
                    <div class="txn-stat-label">Total Volume</div>
                    <div class="txn-stat-value">KES {{ fmtCompact(summary.total_volume) }}</div>
                </div>
                <div class="txn-stat">
                    <div class="txn-stat-label">This Month</div>
                    <div class="txn-stat-value">KES {{ fmtCompact(summary.month_volume) }}</div>
                </div>
                <div class="txn-stat">
                    <div class="txn-stat-label">Transactions</div>
                    <div class="txn-stat-value">{{ summary.transaction_count ?? 0 }}</div>
                </div>
                <div class="txn-stat">
                    <div class="txn-stat-label">Success Rate</div>
                    <div class="txn-stat-value">{{ summary.success_rate ?? 0 }}%</div>
                </div>
            </div>
        </div>

        <!-- ── Filters ── -->
        <div class="txn-filters-bar">
            <div class="txn-search-wrap">
                <svg class="txn-search-icon" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input
                    v-model="search"
                    type="text"
                    class="txn-search"
                    placeholder="Search reference, payer…"
                />
                <button v-if="search" class="txn-search-clear" @click="search = ''">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="txn-filter-selects">
                <select v-model="status" class="txn-select" @change="applyFilters">
                    <option value="">All Status</option>
                    <option value="completed">Completed</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="failed">Failed</option>
                    <option value="reversed">Reversed</option>
                </select>

                <select v-model="channel" class="txn-select" @change="applyFilters">
                    <option value="">All Channels</option>
                    <option value="mpesa">M-Pesa</option>
                    <option value="card">Card</option>
                    <option value="bank_transfer">Bank Transfer</option>
                </select>

                <select v-model="type" class="txn-select" @change="applyFilters">
                    <option value="">All Types</option>
                    <option value="payout">Payout</option>
                    <option value="collection">Collection</option>
                </select>
            </div>

            <div class="txn-filter-actions">
                <button class="txn-btn-filter" @click="applyFilters">
                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L13 10.414V15a1 1 0 01-.553.894l-4 2A1 1 0 017 17v-6.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"/></svg>
                    Filter
                </button>
                <button v-if="hasActiveFilters" class="txn-btn-reset" @click="resetFilters">
                    Reset
                </button>
            </div>
        </div>

        <!-- ── Table (desktop) ── -->
        <div class="txn-table-wrap">
            <table class="txn-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Type</th>
                        <th>Channel</th>
                        <th class="txn-col-payer">Payer / Description</th>
                        <th class="txn-col-amount">Amount</th>
                        <th>Status</th>
                        <th class="txn-col-date">Date</th>
                    </tr>
                </thead>
                <tbody v-if="transactions.length">
                    <tr
                        v-for="txn in transactions"
                        :key="txn.id ?? txn.reference"
                        class="txn-row"
                        @click="router.visit(route('gwinto.reava-pay.transactions.show', txn.id ?? txn.reference))"
                    >
                        <td>
                            <div class="txn-ref">
                                <span class="txn-ref-text">{{ txn.reference ?? txn.gwinto_reference ?? '—' }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="txn-type-badge" :class="typeClass(txn.type)">
                                {{ typeIcon(txn.type) }} {{ txn.type ?? '—' }}
                            </span>
                        </td>
                        <td>
                            <span v-if="txn.channel" class="txn-channel-badge" :class="getChannel(txn.channel).cls">
                                {{ getChannel(txn.channel).icon }} {{ getChannel(txn.channel).label }}
                            </span>
                            <span v-else class="txn-muted">—</span>
                        </td>
                        <td class="txn-col-payer">
                            <span class="txn-payer">{{ txn.payer ?? txn.recipient ?? txn.description ?? '—' }}</span>
                        </td>
                        <td class="txn-col-amount txn-amount">
                            {{ fmt(txn.amount, txn.currency ?? 'KES') }}
                        </td>
                        <td>
                            <span class="txn-status" :class="getStatus(txn.status).cls">
                                {{ getStatus(txn.status).label }}
                            </span>
                        </td>
                        <td class="txn-col-date">
                            <div class="txn-date">{{ fmtDate(txn.created_at ?? txn.date) }}</div>
                            <div class="txn-time">{{ fmtTime(txn.created_at ?? txn.date) }}</div>
                        </td>
                    </tr>
                </tbody>
                <tbody v-else>
                    <tr>
                        <td colspan="7" class="txn-empty">
                            <div class="txn-empty-inner">
                                <svg width="40" height="40" fill="none" stroke="#d1d5db" stroke-width="1.5" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                <p>No transactions found</p>
                                <button v-if="hasActiveFilters" class="txn-btn-reset" @click="resetFilters">Clear filters</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ── Mobile cards ── -->
        <div class="txn-mobile-list">
            <div v-if="!transactions.length" class="txn-empty">
                <div class="txn-empty-inner">
                    <svg width="40" height="40" fill="none" stroke="#d1d5db" stroke-width="1.5" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <p>No transactions found</p>
                </div>
            </div>
            <Link
                v-for="txn in transactions"
                :key="txn.id ?? txn.reference"
                :href="route('gwinto.reava-pay.transactions.show', txn.id ?? txn.reference)"
                class="txn-mobile-card"
            >
                <div class="txn-mobile-top">
                    <div class="txn-mobile-ref">
                        <span class="txn-mobile-ref-text">{{ txn.reference ?? '—' }}</span>
                        <span class="txn-type-badge" :class="typeClass(txn.type)">{{ txn.type ?? '—' }}</span>
                    </div>
                    <div class="txn-mobile-amount">{{ fmt(txn.amount, txn.currency ?? 'KES') }}</div>
                </div>
                <div class="txn-mobile-bottom">
                    <span v-if="txn.channel" class="txn-channel-badge" :class="getChannel(txn.channel).cls">
                        {{ getChannel(txn.channel).icon }} {{ getChannel(txn.channel).label }}
                    </span>
                    <span class="txn-status" :class="getStatus(txn.status).cls">{{ getStatus(txn.status).label }}</span>
                    <span class="txn-mobile-date">{{ fmtDate(txn.created_at) }}</span>
                </div>
            </Link>
        </div>

        <!-- ── Pagination ── -->
        <div v-if="pagination.last_page > 1" class="txn-pagination">
            <div class="txn-pagination-info">
                Showing {{ pagination.from ?? 1 }}–{{ pagination.to ?? transactions.length }} of {{ pagination.total }}
            </div>
            <div class="txn-pagination-pages">
                <button
                    class="txn-page-btn"
                    :disabled="pagination.current_page <= 1"
                    @click="goToPage(pagination.current_page - 1)"
                >
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
                </button>
                <template v-for="p in pages" :key="p">
                    <span v-if="p === '…'" class="txn-page-ellipsis">…</span>
                    <button
                        v-else
                        class="txn-page-btn"
                        :class="{ 'txn-page-btn--active': p === pagination.current_page }"
                        @click="goToPage(p)"
                    >{{ p }}</button>
                </template>
                <button
                    class="txn-page-btn"
                    :disabled="pagination.current_page >= pagination.last_page"
                    @click="goToPage(pagination.current_page + 1)"
                >
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                </button>
            </div>
        </div>

    </div>
</template>

<style scoped>
/* ── Base ── */
.txn-page { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #111827; background: #f9fafb; min-height: 100vh; }

/* ── Hero ── */
.txn-hero {
    background: linear-gradient(135deg, #0f766e 0%, #0891b2 60%, #1d4ed8 100%);
    color: #fff; padding: 20px 20px 0;
}
.txn-hero-top {
    display: flex; align-items: flex-start; gap: 12px;
    justify-content: space-between; margin-bottom: 18px; flex-wrap: nowrap;
}
.txn-hero-brand { display: flex; align-items: flex-start; gap: 10px; flex: 1; min-width: 0; }
.txn-hero-icon {
    background: rgba(255,255,255,0.15); border-radius: 10px; padding: 7px;
    display: flex; align-items: center; justify-content: center;
    backdrop-filter: blur(4px); flex-shrink: 0; margin-top: 2px;
}
.txn-hero-title { font-size: 0.98rem; font-weight: 700; line-height: 1.25; }
.txn-hero-sub { font-size: 0.72rem; opacity: 0.8; margin-top: 2px; line-height: 1.3; }

.txn-settings-btn {
    display: flex; align-items: center; gap: 5px;
    padding: 6px 11px; border-radius: 9px; font-size: 0.75rem; font-weight: 600;
    background: rgba(255,255,255,0.15); color: #fff; text-decoration: none;
    border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(4px);
    transition: background 0.15s; white-space: nowrap; flex-shrink: 0;
}
.txn-settings-btn:hover { background: rgba(255,255,255,0.25); }

/* Stats — 4-column on desktop, 2×2 grid on mobile */
.txn-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    background: rgba(0,0,0,0.18);
    border-radius: 10px 10px 0 0;
    margin: 0 -20px;
    overflow: hidden;
}
.txn-stat {
    padding: 13px 16px; text-align: center;
    border-right: 1px solid rgba(255,255,255,0.1);
}
.txn-stat:last-child { border-right: none; }
.txn-stat-label { font-size: 0.67rem; opacity: 0.75; text-transform: uppercase; letter-spacing: 0.04em; }
.txn-stat-value { font-size: 0.95rem; font-weight: 700; margin-top: 3px; }

@media (max-width: 500px) {
    .txn-hero { padding: 16px 16px 0; }
    .txn-stats {
        grid-template-columns: repeat(2, 1fr);
        margin: 0 -16px;
    }
    .txn-stat {
        border-right: 1px solid rgba(255,255,255,0.1);
        border-bottom: 1px solid rgba(255,255,255,0.1);
        padding: 11px 12px;
    }
    .txn-stat:nth-child(2n) { border-right: none; }
    .txn-stat:nth-child(3), .txn-stat:nth-child(4) { border-bottom: none; }
    .txn-stat-value { font-size: 0.88rem; }
    .txn-hero-title { font-size: 0.9rem; }
    .txn-settings-btn { padding: 5px 9px; font-size: 0.72rem; }
}

/* ── Filters ── */
.txn-filters-bar {
    display: flex; align-items: center; gap: 10px;
    flex-wrap: wrap; padding: 16px 20px;
    background: #fff; border-bottom: 1px solid #e5e7eb;
    position: sticky; top: 0; z-index: 10; box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}

.txn-search-wrap { position: relative; flex: 1; min-width: 180px; }
.txn-search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #9ca3af; pointer-events: none; }
.txn-search {
    width: 100%; padding: 8px 32px 8px 34px; border: 1.5px solid #e5e7eb; border-radius: 8px;
    font-size: 0.83rem; color: #111827; outline: none; background: #f9fafb; box-sizing: border-box;
    transition: border-color 0.15s, background 0.15s;
}
.txn-search:focus { border-color: #0f766e; background: #fff; box-shadow: 0 0 0 3px rgba(15,118,110,0.1); }
.txn-search-clear { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #9ca3af; padding: 2px; display: flex; }
.txn-search-clear:hover { color: #374151; }

.txn-filter-selects { display: flex; gap: 8px; flex-wrap: wrap; }
.txn-select {
    padding: 7px 28px 7px 10px; border: 1.5px solid #e5e7eb; border-radius: 8px;
    font-size: 0.8rem; color: #374151; background: #f9fafb; cursor: pointer; outline: none;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 10 10'%3E%3Cpath fill='%236b7280' d='M5 7L1 3h8z'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 8px center;
    transition: border-color 0.15s;
}
.txn-select:focus { border-color: #0f766e; background-color: #fff; box-shadow: 0 0 0 3px rgba(15,118,110,0.1); }

.txn-filter-actions { display: flex; gap: 8px; align-items: center; }
.txn-btn-filter {
    display: flex; align-items: center; gap: 6px;
    padding: 8px 14px; border-radius: 8px; font-size: 0.82rem; font-weight: 600;
    background: linear-gradient(135deg, #0f766e, #0891b2); color: #fff; border: none; cursor: pointer;
    transition: opacity 0.15s;
}
.txn-btn-filter:hover { opacity: 0.9; }
.txn-btn-reset {
    padding: 7px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: 500;
    background: #fff; color: #374151; border: 1.5px solid #e5e7eb; cursor: pointer;
    transition: all 0.15s;
}
.txn-btn-reset:hover { background: #f3f4f6; border-color: #9ca3af; }

/* ── Table ── */
.txn-table-wrap { overflow-x: auto; background: #fff; border-top: none; display: block; }
@media (max-width: 720px) { .txn-table-wrap { display: none; } }

.txn-table { width: 100%; border-collapse: collapse; font-size: 0.83rem; }
.txn-table thead tr { background: #f9fafb; border-bottom: 2px solid #e5e7eb; }
.txn-table th {
    padding: 11px 16px; text-align: left; font-size: 0.72rem; font-weight: 700;
    color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap;
}
.txn-table td { padding: 13px 16px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
.txn-col-payer { max-width: 180px; }
.txn-col-amount { text-align: right; }
.txn-col-date { white-space: nowrap; }

.txn-row { cursor: pointer; transition: background 0.1s; }
.txn-row:hover { background: #f8fafc; }
.txn-row:hover .txn-ref-text { color: #0f766e; }

.txn-ref-text { font-family: 'SF Mono', 'Fira Code', monospace; font-size: 0.78rem; color: #374151; font-weight: 500; word-break: break-all; transition: color 0.1s; }
.txn-payer { color: #6b7280; font-size: 0.78rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.txn-amount { font-weight: 700; color: #111827; }
.txn-date { font-size: 0.78rem; color: #374151; font-weight: 500; }
.txn-time { font-size: 0.7rem; color: #9ca3af; margin-top: 1px; }
.txn-muted { color: #9ca3af; }

/* ── Mobile cards ── */
.txn-mobile-list { display: none; flex-direction: column; gap: 0; }
@media (max-width: 720px) { .txn-mobile-list { display: flex; } }

.txn-mobile-card {
    display: block; padding: 14px 16px; border-bottom: 1px solid #f3f4f6;
    background: #fff; text-decoration: none; transition: background 0.1s;
}
.txn-mobile-card:hover { background: #f9fafb; }
.txn-mobile-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 8px; }
.txn-mobile-ref { flex: 1; min-width: 0; }
.txn-mobile-ref-text { display: block; font-family: 'SF Mono', 'Fira Code', monospace; font-size: 0.78rem; color: #374151; font-weight: 500; word-break: break-all; margin-bottom: 4px; }
.txn-mobile-amount { font-size: 0.95rem; font-weight: 700; color: #111827; white-space: nowrap; flex-shrink: 0; }
.txn-mobile-bottom { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.txn-mobile-date { font-size: 0.72rem; color: #9ca3af; margin-left: auto; }

/* ── Type badge ── */
.txn-type-badge {
    display: inline-flex; align-items: center; gap: 3px;
    font-size: 0.7rem; font-weight: 600; padding: 2px 8px; border-radius: 999px;
    text-transform: capitalize;
}
.type--payout { background: #eff6ff; color: #1d4ed8; }
.type--collection { background: #f0fdf4; color: #15803d; }

/* ── Channel badge ── */
.txn-channel-badge {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 0.72rem; font-weight: 600; padding: 3px 9px; border-radius: 999px;
}
.ch--mpesa { background: #f0fff4; color: #15803d; border: 1px solid #bbf7d0; }
.ch--card { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.ch--bank { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
.ch--default { background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb; }

/* ── Status badge ── */
.txn-status {
    display: inline-flex; align-items: center;
    font-size: 0.7rem; font-weight: 700; padding: 3px 9px; border-radius: 999px;
    letter-spacing: 0.02em;
}
.status--success { background: #dcfce7; color: #15803d; }
.status--pending { background: #fef9c3; color: #854d0e; }
.status--processing { background: #eff6ff; color: #1d4ed8; }
.status--failed { background: #fee2e2; color: #dc2626; }
.status--reversed { background: #f3e8ff; color: #7c3aed; }
.status--cancelled { background: #f3f4f6; color: #6b7280; }
.status--default { background: #f3f4f6; color: #6b7280; }

/* ── Empty state ── */
.txn-empty { padding: 48px 16px; text-align: center; }
.txn-empty-inner { display: flex; flex-direction: column; align-items: center; gap: 10px; color: #9ca3af; font-size: 0.85rem; }

/* ── Pagination ── */
.txn-pagination {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 20px; background: #fff; border-top: 1px solid #e5e7eb;
    flex-wrap: wrap; gap: 12px;
}
.txn-pagination-info { font-size: 0.78rem; color: #6b7280; }
.txn-pagination-pages { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; }
.txn-page-btn {
    min-width: 34px; height: 34px; padding: 0 8px; border-radius: 7px;
    border: 1.5px solid #e5e7eb; background: #fff; font-size: 0.82rem; font-weight: 500;
    color: #374151; cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: all 0.15s;
}
.txn-page-btn:hover:not(:disabled) { background: #f3f4f6; border-color: #9ca3af; }
.txn-page-btn--active { background: #0f766e; color: #fff; border-color: #0f766e; }
.txn-page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.txn-page-ellipsis { padding: 0 4px; color: #9ca3af; font-size: 0.85rem; }

@media (max-width: 560px) {
    .txn-filters-bar { gap: 8px; padding: 10px 12px; }
    .txn-search-wrap { min-width: 0; }
    .txn-filter-selects { width: 100%; display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
    .txn-select { font-size: 0.76rem; padding: 6px 24px 6px 8px; }
    .txn-filter-actions { width: 100%; display: flex; gap: 8px; }
    .txn-btn-filter { flex: 1; justify-content: center; }
    .txn-btn-reset { flex: 0 0 auto; }
    .txn-pagination { justify-content: center; }
    .txn-pagination-info { width: 100%; text-align: center; }
}
</style>
