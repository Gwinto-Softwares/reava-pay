<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    transaction: { type: Object, default: null },
    transactionId: { type: String, default: '' },
});

const txn = computed(() => props.transaction ?? {});

const copyRef = ref(false);
const copyToClipboard = async (text) => {
    try { await navigator.clipboard.writeText(text); } catch { /* noop */ }
    copyRef.value = true;
    setTimeout(() => { copyRef.value = false; }, 2000);
};

// ── Formatters ──
const fmt = (amount, currency = 'KES') =>
    new Intl.NumberFormat('en-KE', { style: 'currency', currency, minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount ?? 0);

const fmtDate = (d) => {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-KE', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const fmtDateShort = (d) => {
    if (!d) return null;
    return new Date(d).toLocaleDateString('en-KE', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const shortRef = (ref, max = 24) => {
    if (!ref) return '—';
    return ref.length > max ? ref.slice(0, max) + '…' : ref;
};

// ── Status ──
const statusConfig = {
    completed: { label: 'Completed', cls: 'st--success', icon: '✓' },
    success: { label: 'Completed', cls: 'st--success', icon: '✓' },
    pending: { label: 'Pending', cls: 'st--pending', icon: '⏳' },
    processing: { label: 'Processing', cls: 'st--processing', icon: '↻' },
    failed: { label: 'Failed', cls: 'st--failed', icon: '✗' },
    reversed: { label: 'Reversed', cls: 'st--reversed', icon: '↩' },
    cancelled: { label: 'Cancelled', cls: 'st--cancelled', icon: '⊘' },
};
const getStatus = (s) => statusConfig[s?.toLowerCase()] ?? { label: s ?? 'Unknown', cls: 'st--default', icon: '?' };

const channelConfig = {
    mpesa: { label: 'M-Pesa', icon: '📱', cls: 'ch--mpesa' },
    m_pesa: { label: 'M-Pesa', icon: '📱', cls: 'ch--mpesa' },
    card: { label: 'Card', icon: '💳', cls: 'ch--card' },
    bank_transfer: { label: 'Bank Transfer', icon: '🏦', cls: 'ch--bank' },
};
const getChannel = (c) => channelConfig[c?.toLowerCase()] ?? { label: c ?? '—', icon: '💱', cls: 'ch--default' };

const isTerminal = computed(() => ['completed', 'success', 'failed', 'reversed', 'cancelled'].includes(txn.value.status?.toLowerCase()));
const isSuccess = computed(() => ['completed', 'success'].includes(txn.value.status?.toLowerCase()));

// ── Timeline steps ──
const timeline = computed(() => {
    const steps = [];
    if (txn.value.created_at) {
        steps.push({ label: 'Initiated', date: fmtDateShort(txn.value.created_at), done: true, icon: 'init' });
    }
    if (['processing', 'completed', 'success', 'failed', 'reversed'].includes(txn.value.status?.toLowerCase())) {
        steps.push({ label: 'Processing', date: null, done: true, icon: 'processing' });
    }
    if (isSuccess.value) {
        steps.push({ label: 'Completed', date: fmtDateShort(txn.value.completed_at ?? txn.value.updated_at), done: true, icon: 'done' });
    } else if (txn.value.status?.toLowerCase() === 'failed') {
        steps.push({ label: 'Failed', date: fmtDateShort(txn.value.failed_at ?? txn.value.updated_at), done: false, failed: true, icon: 'fail' });
    } else if (!isTerminal.value) {
        steps.push({ label: 'Awaiting completion', date: null, done: false, pending: true, icon: 'wait' });
    }
    return steps;
});

const metaCount = computed(() => {
    const m = txn.value.metadata;
    if (!m) return 0;
    return typeof m === 'object' ? Object.keys(m).length : 0;
});

const linkedRecords = computed(() => {
    const records = [];
    if (txn.value.wallet_transaction_id) records.push({ label: 'Wallet Transaction', value: txn.value.wallet_transaction_id, icon: '💼' });
    if (txn.value.invoice_id) records.push({ label: 'Invoice', value: txn.value.invoice_id, icon: '📄' });
    if (txn.value.payout_request_id) records.push({ label: 'Payout Request', value: txn.value.payout_request_id, icon: '📤' });
    return records;
});
</script>

<template>
    <div class="detail-page">

        <!-- ── Breadcrumb ── -->
        <div class="detail-breadcrumb">
            <Link :href="route('gwinto.reava-pay.settings')" class="bc-item">
                <svg width="13" height="13" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                Reava Pay
            </Link>
            <svg class="bc-sep" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
            <Link :href="route('gwinto.reava-pay.transactions.index')" class="bc-item">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Transactions
            </Link>
            <svg class="bc-sep" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
            <span class="bc-current">
                <span class="bc-ref-full">{{ txn.reference ?? transactionId }}</span>
                <span class="bc-ref-short">{{ shortRef(txn.reference ?? transactionId, 18) }}</span>
            </span>
        </div>

        <!-- ── Hero card ── -->
        <div class="detail-hero" :class="isSuccess ? 'detail-hero--success' : txn.status === 'failed' ? 'detail-hero--failed' : 'detail-hero--neutral'">
            <div class="detail-hero-inner">
                <div class="detail-hero-left">
                    <div v-if="txn.channel" class="detail-channel-badge" :class="getChannel(txn.channel).cls">
                        {{ getChannel(txn.channel).icon }} {{ getChannel(txn.channel).label }}
                    </div>
                    <div class="detail-amount">{{ fmt(txn.amount, txn.currency ?? 'KES') }}</div>
                    <div class="detail-ref-row">
                        <span class="detail-ref">{{ txn.reference ?? transactionId }}</span>
                        <button class="detail-copy-btn" @click="copyToClipboard(txn.reference ?? transactionId)" :title="copyRef ? 'Copied!' : 'Copy reference'">
                            <svg v-if="!copyRef" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                            <svg v-else width="13" height="13" fill="none" stroke="#4ade80" stroke-width="2" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
                        </button>
                    </div>
                    <div class="detail-meta-row">
                        <span class="detail-type">{{ txn.type ?? '—' }}</span>
                        <span class="detail-dot">·</span>
                        <span class="detail-date">{{ fmtDate(txn.created_at ?? txn.date) }}</span>
                    </div>
                </div>
                <div class="detail-hero-right">
                    <span class="detail-status-large" :class="getStatus(txn.status).cls">
                        {{ getStatus(txn.status).label }}
                    </span>
                </div>
            </div>
        </div>

        <!-- ── Body grid ── -->
        <div class="detail-grid">

            <!-- Left column -->
            <div class="detail-col-main">

                <!-- Transaction Details -->
                <div class="detail-card">
                    <div class="detail-card-header">
                        <div class="detail-card-icon detail-card-icon--blue">
                            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                        <span class="detail-card-title">Transaction Details</span>
                    </div>

                    <div class="detail-table">
                        <div class="detail-row">
                            <span class="detail-key">Gwinto Reference</span>
                            <span class="detail-val detail-mono">{{ txn.reference ?? txn.gwinto_reference ?? '—' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-key">Reava Pay Reference</span>
                            <span class="detail-val detail-mono">{{ txn.reava_pay_reference ?? txn.external_reference ?? '—' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-key">Provider Reference</span>
                            <span class="detail-val detail-mono">{{ txn.provider_reference ?? '—' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-key">Type</span>
                            <span class="detail-val">
                                <span class="detail-type-tag" :class="txn.type?.toLowerCase() === 'payout' ? 'type--payout' : 'type--collection'">
                                    {{ txn.type ?? '—' }}
                                </span>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-key">Channel</span>
                            <span class="detail-val">
                                <span v-if="txn.channel" class="detail-ch-tag" :class="getChannel(txn.channel).cls">
                                    {{ getChannel(txn.channel).icon }} {{ getChannel(txn.channel).label }}
                                </span>
                                <span v-else>—</span>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-key">Amount</span>
                            <span class="detail-val detail-amount-sm">{{ fmt(txn.amount, txn.currency ?? 'KES') }}</span>
                        </div>
                        <div class="detail-row" v-if="txn.fee != null">
                            <span class="detail-key">Fee</span>
                            <span class="detail-val">{{ fmt(txn.fee, txn.currency ?? 'KES') }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-key">Status</span>
                            <span class="detail-val">
                                <span class="detail-status-sm" :class="getStatus(txn.status).cls">
                                    {{ getStatus(txn.status).label }}
                                </span>
                            </span>
                        </div>
                        <div v-if="txn.failure_reason" class="detail-row">
                            <span class="detail-key">Failure Reason</span>
                            <span class="detail-val detail-danger">{{ txn.failure_reason }}</span>
                        </div>
                    </div>
                </div>

                <!-- Parties -->
                <div class="detail-card">
                    <div class="detail-card-header">
                        <div class="detail-card-icon detail-card-icon--purple">
                            <svg width="15" height="15" fill="currentColor" viewBox="0 0 20 20"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v1h8v-1zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/></svg>
                        </div>
                        <span class="detail-card-title">Parties</span>
                    </div>
                    <div class="detail-table">
                        <div v-if="txn.payer || txn.sender" class="detail-row">
                            <span class="detail-key">{{ txn.type?.toLowerCase() === 'payout' ? 'Sender' : 'Payer' }}</span>
                            <span class="detail-val">{{ txn.payer ?? txn.sender ?? '—' }}</span>
                        </div>
                        <div v-if="txn.recipient || txn.receiver" class="detail-row">
                            <span class="detail-key">Recipient</span>
                            <span class="detail-val">{{ txn.recipient ?? txn.receiver ?? '—' }}</span>
                        </div>
                        <div v-if="txn.phone" class="detail-row">
                            <span class="detail-key">Phone</span>
                            <span class="detail-val detail-mono">{{ txn.phone }}</span>
                        </div>
                        <div v-if="txn.description" class="detail-row">
                            <span class="detail-key">Description</span>
                            <span class="detail-val detail-desc">{{ txn.description }}</span>
                        </div>
                        <div v-if="!txn.payer && !txn.sender && !txn.recipient && !txn.description">
                            <p class="detail-empty">No party information available.</p>
                        </div>
                    </div>
                </div>

            </div><!-- /.detail-col-main -->

            <!-- Right column -->
            <div class="detail-col-side">

                <!-- Timeline -->
                <div class="detail-card">
                    <div class="detail-card-header">
                        <div class="detail-card-icon detail-card-icon--green">
                            <svg width="15" height="15" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                        </div>
                        <span class="detail-card-title">Timeline</span>
                    </div>
                    <div class="tl-list">
                        <div
                            v-for="(step, i) in timeline"
                            :key="i"
                            class="tl-step"
                            :class="{
                                'tl-step--done': step.done && !step.failed,
                                'tl-step--fail': step.failed,
                                'tl-step--pending': step.pending,
                            }"
                        >
                            <div class="tl-dot">
                                <svg v-if="step.done && !step.failed" width="10" height="10" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                <svg v-else-if="step.failed" width="10" height="10" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                <div v-else class="tl-dot-inner"></div>
                            </div>
                            <div class="tl-info">
                                <div class="tl-label">{{ step.label }}</div>
                                <div v-if="step.date" class="tl-date">{{ step.date }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Linked Records -->
                <div v-if="linkedRecords.length" class="detail-card">
                    <div class="detail-card-header">
                        <div class="detail-card-icon detail-card-icon--amber">
                            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
                        </div>
                        <span class="detail-card-title">Linked Records</span>
                    </div>
                    <div class="detail-table">
                        <div v-for="rec in linkedRecords" :key="rec.label" class="detail-row">
                            <span class="detail-key">{{ rec.label }}</span>
                            <span class="detail-val detail-mono detail-link-val">#{{ rec.value }}</span>
                        </div>
                    </div>
                </div>

                <!-- System Info -->
                <div class="detail-card">
                    <div class="detail-card-header">
                        <div class="detail-card-icon detail-card-icon--gray">
                            <svg width="15" height="15" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm3.293 1.293a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 01-1.414-1.414L7.586 10 5.293 7.707a1 1 0 010-1.414zM11 12a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
                        </div>
                        <span class="detail-card-title">System Info</span>
                    </div>
                    <div class="detail-table">
                        <div class="detail-row">
                            <span class="detail-key">Meta Count</span>
                            <span class="detail-val">{{ metaCount }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-key">Created</span>
                            <span class="detail-val">{{ fmtDate(txn.created_at) }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-key">Last Updated</span>
                            <span class="detail-val">{{ fmtDate(txn.updated_at) }}</span>
                        </div>
                        <div v-if="txn.id" class="detail-row">
                            <span class="detail-key">Internal ID</span>
                            <span class="detail-val detail-mono">#{{ txn.id }}</span>
                        </div>
                    </div>
                </div>

            </div><!-- /.detail-col-side -->
        </div><!-- /.detail-grid -->

        <!-- ── Back button ── -->
        <div class="detail-back-bar">
            <Link :href="route('gwinto.reava-pay.transactions.index')" class="detail-back-btn">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Back to Transactions
            </Link>
        </div>

    </div>
</template>

<style scoped>
/* ── Base ── */
.detail-page { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #111827; background: #f9fafb; min-height: 100vh; }

/* ── Breadcrumb ── */
.detail-breadcrumb {
    display: flex; align-items: center; gap: 4px; flex-wrap: nowrap;
    padding: 12px 20px; background: #fff; border-bottom: 1px solid #e5e7eb;
    overflow: hidden; font-size: 0.8rem;
}
.bc-item {
    display: flex; align-items: center; gap: 5px; color: #6b7280; text-decoration: none;
    white-space: nowrap; flex-shrink: 0; transition: color 0.15s;
}
.bc-item:hover { color: #0f766e; }
.bc-sep { color: #d1d5db; flex-shrink: 0; }
.bc-current {
    color: #111827; font-weight: 600; font-family: 'SF Mono', 'Fira Code', monospace;
    min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
/* Show truncated ref on mobile, full on desktop */
.bc-ref-full { display: inline; }
.bc-ref-short { display: none; }
@media (max-width: 520px) {
    .bc-ref-full { display: none; }
    .bc-ref-short { display: inline; }
}

/* ── Hero ── */
.detail-hero {
    padding: 24px 20px;
    color: #fff;
}
.detail-hero--success { background: linear-gradient(135deg, #0f766e 0%, #0891b2 100%); }
.detail-hero--failed { background: linear-gradient(135deg, #7f1d1d 0%, #991b1b 100%); }
.detail-hero--neutral { background: linear-gradient(135deg, #1e3a5f 0%, #1d4ed8 100%); }

.detail-hero-inner { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.detail-hero-left { flex: 1; min-width: 0; }

.detail-channel-badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 0.75rem; font-weight: 700; padding: 3px 10px; border-radius: 999px;
    margin-bottom: 10px;
}
.ch--mpesa { background: rgba(0,166,81,0.2); color: #86efac; border: 1px solid rgba(134,239,172,0.4); }
.ch--card { background: rgba(29,78,216,0.2); color: #93c5fd; border: 1px solid rgba(147,197,253,0.4); }
.ch--bank { background: rgba(217,119,6,0.2); color: #fcd34d; border: 1px solid rgba(252,211,77,0.4); }
.ch--default { background: rgba(255,255,255,0.15); color: #e5e7eb; border: 1px solid rgba(255,255,255,0.2); }

.detail-amount { font-size: 2rem; font-weight: 800; letter-spacing: -0.02em; line-height: 1; margin-bottom: 8px; }
@media (max-width: 400px) { .detail-amount { font-size: 1.6rem; } }

.detail-ref-row { display: flex; align-items: center; gap: 6px; margin-bottom: 6px; flex-wrap: wrap; }
.detail-ref { font-family: 'SF Mono', 'Fira Code', monospace; font-size: 0.82rem; opacity: 0.85; word-break: break-all; }
.detail-copy-btn { background: rgba(255,255,255,0.15); border: none; border-radius: 5px; padding: 3px 6px; cursor: pointer; color: white; opacity: 0.8; display: flex; transition: opacity 0.15s; }
.detail-copy-btn:hover { opacity: 1; }

.detail-meta-row { display: flex; align-items: center; gap: 8px; font-size: 0.8rem; opacity: 0.8; flex-wrap: wrap; }
.detail-dot { opacity: 0.5; }
.detail-type { font-weight: 600; text-transform: capitalize; }

.detail-status-large {
    font-size: 0.78rem; font-weight: 700; padding: 5px 14px; border-radius: 999px;
    background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3);
    white-space: nowrap; align-self: flex-start; flex-shrink: 0;
}

/* ── Grid layout ── */
.detail-grid {
    display: grid; grid-template-columns: 1fr 320px; gap: 16px;
    padding: 16px 20px; align-items: start;
}
@media (max-width: 860px) { .detail-grid { grid-template-columns: 1fr; } }
@media (max-width: 860px) { .detail-col-side { order: -1; } }

.detail-col-main, .detail-col-side { display: flex; flex-direction: column; gap: 14px; }

/* ── Cards ── */
.detail-card {
    background: #fff; border-radius: 12px; padding: 18px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 4px 12px rgba(0,0,0,0.05);
    border: 1px solid #f3f4f6;
}
.detail-card-header { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; }
.detail-card-icon {
    width: 30px; height: 30px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.detail-card-icon--blue { background: #eff6ff; color: #1d4ed8; }
.detail-card-icon--purple { background: #f5f3ff; color: #7c3aed; }
.detail-card-icon--green { background: #f0fdf4; color: #16a34a; }
.detail-card-icon--amber { background: #fffbeb; color: #d97706; }
.detail-card-icon--gray { background: #f3f4f6; color: #6b7280; }
.detail-card-title { font-size: 0.88rem; font-weight: 700; color: #111827; }

/* ── Detail table rows ── */
.detail-table { display: flex; flex-direction: column; gap: 0; }
.detail-row {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 16px; padding: 10px 0; border-bottom: 1px solid #f9fafb;
}
.detail-row:last-child { border-bottom: none; }
.detail-key { font-size: 0.78rem; color: #6b7280; font-weight: 500; flex-shrink: 0; min-width: 130px; }
.detail-val { font-size: 0.83rem; color: #111827; font-weight: 500; text-align: right; word-break: break-word; flex: 1; }
.detail-mono { font-family: 'SF Mono', 'Fira Code', monospace; font-size: 0.76rem; }
.detail-amount-sm { font-weight: 700; font-size: 0.9rem; }
.detail-danger { color: #dc2626; }
.detail-desc { text-align: right; line-height: 1.4; font-size: 0.78rem; }
.detail-link-val { color: #2563eb; }
.detail-empty { font-size: 0.8rem; color: #9ca3af; padding: 8px 0; text-align: center; }

/* Type/Channel tags inline */
.detail-type-tag {
    font-size: 0.72rem; font-weight: 600; padding: 2px 8px; border-radius: 999px;
    text-transform: capitalize;
}
.type--payout { background: #eff6ff; color: #1d4ed8; }
.type--collection { background: #f0fdf4; color: #15803d; }

.detail-ch-tag {
    font-size: 0.72rem; font-weight: 600; padding: 2px 8px; border-radius: 999px;
    display: inline-flex; align-items: center; gap: 4px;
}

/* Status small */
.detail-status-sm {
    font-size: 0.7rem; font-weight: 700; padding: 2px 9px; border-radius: 999px;
    display: inline-flex; align-items: center; letter-spacing: 0.02em;
}
.st--success { background: #dcfce7; color: #15803d; }
.st--pending { background: #fef9c3; color: #854d0e; }
.st--processing { background: #eff6ff; color: #1d4ed8; }
.st--failed { background: #fee2e2; color: #dc2626; }
.st--reversed { background: #f3e8ff; color: #7c3aed; }
.st--cancelled { background: #f3f4f6; color: #6b7280; }
.st--default { background: #f3f4f6; color: #6b7280; }

/* ── Timeline ── */
.tl-list { display: flex; flex-direction: column; gap: 0; }
.tl-step { display: flex; align-items: flex-start; gap: 12px; position: relative; padding-bottom: 18px; }
.tl-step:last-child { padding-bottom: 0; }
.tl-step:not(:last-child)::before {
    content: ''; position: absolute; left: 12px; top: 24px;
    width: 1px; height: calc(100% - 12px);
    background: #e5e7eb;
}
.tl-step--done:not(:last-child)::before { background: #bbf7d0; }
.tl-step--fail:not(:last-child)::before { background: #fecaca; }

.tl-dot {
    width: 25px; height: 25px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; border: 2px solid #e5e7eb; background: #fff; z-index: 1;
}
.tl-step--done .tl-dot { background: #16a34a; border-color: #16a34a; color: #fff; }
.tl-step--fail .tl-dot { background: #dc2626; border-color: #dc2626; color: #fff; }
.tl-step--pending .tl-dot { border-color: #d97706; }
.tl-dot-inner { width: 8px; height: 8px; border-radius: 50%; background: #d97706; }

.tl-info { flex: 1; padding-top: 3px; }
.tl-label { font-size: 0.82rem; font-weight: 600; color: #111827; }
.tl-step--fail .tl-label { color: #dc2626; }
.tl-step--pending .tl-label { color: #d97706; }
.tl-date { font-size: 0.72rem; color: #6b7280; margin-top: 2px; }

/* ── Back bar ── */
.detail-back-bar { padding: 16px 20px; background: #fff; border-top: 1px solid #e5e7eb; }
.detail-back-btn {
    display: inline-flex; align-items: center; gap: 7px;
    font-size: 0.82rem; font-weight: 600; color: #374151; text-decoration: none;
    padding: 8px 14px; border-radius: 8px; border: 1.5px solid #e5e7eb;
    background: #fff; transition: all 0.15s;
}
.detail-back-btn:hover { background: #f9fafb; border-color: #9ca3af; }

@media (max-width: 520px) {
    .detail-grid { padding: 12px; gap: 12px; }
    .detail-card { padding: 14px; }
    .detail-key { min-width: 100px; font-size: 0.73rem; }
    .detail-val { font-size: 0.78rem; }
    .detail-amount { font-size: 1.7rem; }
    .detail-hero { padding: 18px 16px; }
    .detail-back-bar { padding: 12px 16px; }
    .detail-breadcrumb { padding: 10px 12px; font-size: 0.75rem; }
}
</style>
