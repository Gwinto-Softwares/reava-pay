<script setup>
import { ref, computed, reactive } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    credentials: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
    channels: { type: Object, default: () => ({}) },
    isConnected: { type: Boolean, default: false },
    webhookUrl: { type: String, default: '' },
    environment: { type: String, default: 'live' },
    connectedSince: { type: String, default: null },
    walletSettings: { type: Object, default: () => ({}) },
    flash: { type: Object, default: () => ({}) },
});

// ── Visibility toggles for secret fields ──
const show = reactive({ password: false, apiSecret: false, webhookSecret: false, apiKey: false });

// ── Copy to clipboard ──
const copied = reactive({});
const copyToClipboard = async (text, key) => {
    try {
        await navigator.clipboard.writeText(text);
        copied[key] = true;
        setTimeout(() => { copied[key] = false; }, 2000);
    } catch {
        const el = document.createElement('textarea');
        el.value = text;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        copied[key] = true;
        setTimeout(() => { copied[key] = false; }, 2000);
    }
};

// ── Test connection ──
const testingConnection = ref(false);
const testResult = ref(null);
const testConnection = async () => {
    testingConnection.value = true;
    testResult.value = null;
    try {
        const res = await fetch(route('gwinto.reava-pay.test-connection'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
        });
        const data = await res.json();
        testResult.value = data;
    } catch {
        testResult.value = { success: false, message: 'Network error. Please try again.' };
    } finally {
        testingConnection.value = false;
    }
};

// ── Settings form ──
const form = useForm({
    api_key: '',
    public_key: '',
    api_secret: '',
    webhook_secret: '',
    channels: {
        mpesa: props.channels.mpesa ?? true,
        card: props.channels.card ?? true,
        bank_transfer: props.channels.bank_transfer ?? true,
    },
    auto_credit: props.walletSettings.auto_credit ?? true,
    auto_settle: props.walletSettings.auto_settle ?? false,
    settlement_schedule: props.walletSettings.settlement_schedule ?? 'manual',
    min_settlement_amount: props.walletSettings.min_settlement_amount ?? 1000,
});

const saveSettings = () => {
    form.post(route('gwinto.reava-pay.settings.save'), {
        preserveScroll: true,
    });
};

// ── Disconnect / reconnect ──
const disconnecting = ref(false);
const confirmDisconnect = ref(false);
const disconnect = () => {
    disconnecting.value = true;
    router.post(route('gwinto.reava-pay.disconnect'), {}, {
        onFinish: () => { disconnecting.value = false; confirmDisconnect.value = false; },
        preserveScroll: true,
    });
};

const enabling = ref(false);
const enable = () => {
    enabling.value = true;
    router.post(route('gwinto.reava-pay.enable'), {}, {
        onFinish: () => { enabling.value = false; },
        preserveScroll: true,
    });
};

// ── Formatters ──
const fmt = (amount, currency = 'KES') => {
    return new Intl.NumberFormat('en-KE', {
        style: 'currency', currency,
        minimumFractionDigits: 0, maximumFractionDigits: 0,
    }).format(amount ?? 0);
};

const mask = (str, visibleEnd = 4) => {
    if (!str) return '';
    if (str.length <= visibleEnd) return '•'.repeat(str.length);
    return '•'.repeat(Math.max(str.length - visibleEnd, 8)) + str.slice(-visibleEnd);
};

const truncate = (str, max = 40) => {
    if (!str) return '';
    return str.length > max ? str.slice(0, max) + '…' : str;
};

const paymentChannels = computed(() => [
    {
        key: 'mpesa',
        name: 'M-Pesa',
        description: 'Mobile money via Safaricom',
        icon: '📱',
        color: '#00a651',
        bgColor: '#f0fff6',
        borderColor: '#00a651',
        subtitle: 'STK Push & C2B',
    },
    {
        key: 'card',
        name: 'Card',
        description: 'Visa & Mastercard payments',
        icon: '💳',
        color: '#1a56db',
        bgColor: '#eff6ff',
        borderColor: '#1a56db',
        subtitle: 'Debit & Credit cards',
    },
    {
        key: 'bank_transfer',
        name: 'Bank Transfer',
        description: 'Direct bank payments',
        icon: '🏦',
        color: '#d97706',
        bgColor: '#fffbeb',
        borderColor: '#d97706',
        subtitle: 'EFT & RTGS',
    },
]);

const activeChannelsCount = computed(() =>
    Object.values(form.channels).filter(Boolean).length
);

const connectedSinceFormatted = computed(() => {
    if (!props.connectedSince) return null;
    return new Date(props.connectedSince).toLocaleDateString('en-KE', {
        year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
    });
});
</script>

<template>
    <div class="rp-settings-page">

        <!-- Flash messages -->
        <transition name="slide-down">
            <div v-if="flash.success" class="rp-alert rp-alert-success">
                <svg width="18" height="18" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                {{ flash.success }}
            </div>
        </transition>
        <transition name="slide-down">
            <div v-if="flash.error" class="rp-alert rp-alert-error">
                <svg width="18" height="18" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                {{ flash.error }}
            </div>
        </transition>

        <!-- ── Hero Banner ── -->
        <div class="rp-hero">
            <div class="rp-hero-inner">
                <div class="rp-hero-brand">
                    <div class="rp-hero-logo">
                        <svg width="28" height="28" viewBox="0 0 40 40" fill="none">
                            <rect width="40" height="40" rx="10" fill="white" fill-opacity="0.2"/>
                            <path d="M8 20C8 13.373 13.373 8 20 8s12 5.373 12 12-5.373 12-12 12S8 26.627 8 20z" stroke="white" stroke-width="2" fill="none"/>
                            <path d="M15 20l4 4 6-8" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div>
                        <div class="rp-hero-title">Reava Pay Integration</div>
                        <div class="rp-hero-subtitle">Accept M-Pesa, Card, and Bank payments from your tenants</div>
                    </div>
                </div>
                <div class="rp-hero-badge" :class="isConnected ? 'rp-hero-badge--connected' : 'rp-hero-badge--disconnected'">
                    <span class="rp-pulse-dot" :class="isConnected ? 'rp-pulse-dot--green' : 'rp-pulse-dot--red'"></span>
                    {{ isConnected ? 'Connected' : 'Disconnected' }}
                </div>
            </div>

            <!-- Stats row -->
            <div class="rp-hero-stats">
                <div class="rp-hero-stat">
                    <div class="rp-hero-stat-value">{{ stats.transaction_count ?? 0 }}</div>
                    <div class="rp-hero-stat-label">Transactions</div>
                </div>
                <div class="rp-hero-stat-divider"></div>
                <div class="rp-hero-stat">
                    <div class="rp-hero-stat-value">{{ fmt(stats.total_collected, stats.currency ?? 'KES') }}</div>
                    <div class="rp-hero-stat-label">Total Collected</div>
                </div>
                <div class="rp-hero-stat-divider"></div>
                <div class="rp-hero-stat">
                    <div class="rp-hero-stat-value">{{ fmt(stats.total_settled, stats.currency ?? 'KES') }}</div>
                    <div class="rp-hero-stat-label">Total Settled</div>
                </div>
                <div class="rp-hero-stat-divider"></div>
                <div class="rp-hero-stat">
                    <div class="rp-hero-stat-value">{{ stats.pending_count ?? 0 }}</div>
                    <div class="rp-hero-stat-label">Pending</div>
                </div>
            </div>
        </div>

        <!-- ── Main layout ── -->
        <div class="rp-layout">
            <div class="rp-main">

                <!-- ── Reava Pay Credentials ── -->
                <div class="rp-card">
                    <div class="rp-card-header">
                        <div class="rp-card-icon rp-card-icon--blue">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        </div>
                        <div>
                            <div class="rp-card-title">Reava Pay Credentials</div>
                            <div class="rp-card-desc">Your merchant credentials on Reava Pay. Use these to log in to the dashboard.</div>
                        </div>
                        <div class="rp-ml-auto">
                            <span class="rp-env-badge" :class="environment === 'live' ? 'rp-env-badge--live' : 'rp-env-badge--test'">
                                {{ environment === 'live' ? 'Production' : 'Test' }}
                            </span>
                        </div>
                    </div>

                    <div class="rp-credentials-grid">
                        <!-- Merchant ID -->
                        <div class="rp-cred-field">
                            <label class="rp-cred-label">Merchant ID</label>
                            <div class="rp-cred-value-row">
                                <span class="rp-cred-value">{{ credentials.merchant_id || '—' }}</span>
                                <button v-if="credentials.merchant_id" class="rp-icon-btn" @click="copyToClipboard(credentials.merchant_id, 'merchant_id')" title="Copy">
                                    <svg v-if="!copied['merchant_id']" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                                    <svg v-else width="15" height="15" fill="none" stroke="#16a34a" stroke-width="2" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Login Email -->
                        <div class="rp-cred-field">
                            <label class="rp-cred-label">Login Email</label>
                            <div class="rp-cred-value-row">
                                <span class="rp-cred-value">{{ credentials.login_email || '—' }}</span>
                                <button v-if="credentials.login_email" class="rp-icon-btn" @click="copyToClipboard(credentials.login_email, 'email')" title="Copy">
                                    <svg v-if="!copied['email']" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                                    <svg v-else width="15" height="15" fill="none" stroke="#16a34a" stroke-width="2" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Login Password -->
                        <div class="rp-cred-field rp-cred-field--full">
                            <label class="rp-cred-label">
                                <svg width="13" height="13" fill="currentColor" viewBox="0 0 20 20" style="display:inline;margin-right:4px;vertical-align:middle"><path fill-rule="evenodd" d="M18 8h-1V6a7 7 0 00-14 0v2H2a2 2 0 00-2 2v8a2 2 0 002 2h16a2 2 0 002-2v-8a2 2 0 00-2-2zM7 6a3 3 0 016 0v2H7V6zm3 7a1 1 0 102 0v2a1 1 0 10-2 0v-2z" clip-rule="evenodd"/></svg>
                                Login Password
                            </label>
                            <div class="rp-cred-value-row">
                                <span class="rp-cred-value rp-cred-monospace">{{ show.password ? '••••••••••••' : '••••••••••••' }}</span>
                                <button class="rp-icon-btn" @click="show.password = !show.password" title="Toggle visibility">
                                    <svg v-if="!show.password" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg v-else width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                </button>
                            </div>
                            <a href="https://reavapay.com" target="_blank" class="rp-link-small">
                                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                                Sign in at reavapay.com
                            </a>
                        </div>

                        <!-- Float Account -->
                        <div class="rp-cred-field">
                            <label class="rp-cred-label">Float Account</label>
                            <div class="rp-cred-value-row">
                                <span class="rp-cred-value rp-cred-monospace">{{ credentials.float_account || '—' }}</span>
                                <button v-if="credentials.float_account" class="rp-icon-btn" @click="copyToClipboard(credentials.float_account, 'float')" title="Copy">
                                    <svg v-if="!copied['float']" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                                    <svg v-else width="15" height="15" fill="none" stroke="#16a34a" stroke-width="2" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
                                </button>
                            </div>
                        </div>

                        <!-- API Key -->
                        <div class="rp-cred-field rp-cred-field--full">
                            <label class="rp-cred-label">API Key</label>
                            <div class="rp-cred-value-row">
                                <span class="rp-cred-value rp-cred-monospace rp-cred-truncate">{{ truncate(credentials.api_key) || '—' }}</span>
                                <button v-if="credentials.api_key" class="rp-icon-btn" @click="copyToClipboard(credentials.api_key, 'api_key')" title="Copy">
                                    <svg v-if="!copied['api_key']" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                                    <svg v-else width="15" height="15" fill="none" stroke="#16a34a" stroke-width="2" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
                                </button>
                            </div>
                        </div>

                        <!-- API Secret -->
                        <div class="rp-cred-field rp-cred-field--full">
                            <label class="rp-cred-label">
                                <svg width="13" height="13" fill="currentColor" viewBox="0 0 20 20" style="display:inline;margin-right:4px;vertical-align:middle"><path fill-rule="evenodd" d="M18 8h-1V6a7 7 0 00-14 0v2H2a2 2 0 00-2 2v8a2 2 0 002 2h16a2 2 0 002-2v-8a2 2 0 00-2-2zM7 6a3 3 0 016 0v2H7V6zm3 7a1 1 0 102 0v2a1 1 0 10-2 0v-2z" clip-rule="evenodd"/></svg>
                                API Secret
                            </label>
                            <div class="rp-cred-value-row">
                                <span class="rp-cred-value rp-cred-monospace">{{ show.apiSecret ? (credentials.api_secret || '—') : '••••••••••••••••••••••••••••••••' }}</span>
                                <button class="rp-icon-btn" @click="show.apiSecret = !show.apiSecret" title="Toggle">
                                    <svg v-if="!show.apiSecret" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg v-else width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                </button>
                                <button v-if="credentials.api_key" class="rp-icon-btn" @click="copyToClipboard(credentials.api_secret, 'secret')" title="Copy">
                                    <svg v-if="!copied['secret']" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                                    <svg v-else width="15" height="15" fill="none" stroke="#16a34a" stroke-width="2" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Connected since + actions -->
                    <div v-if="connectedSinceFormatted" class="rp-connected-meta">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        <span>Connected since <strong>{{ connectedSinceFormatted }}</strong></span>
                    </div>

                    <div class="rp-info-box rp-info-box--blue">
                        <svg width="15" height="15" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                        Your Gwinto wallet is synced with your Reava Pay float account. All transactions flow bi-directionally in real-time.
                    </div>

                    <div class="rp-cred-actions">
                        <button
                            class="rp-btn rp-btn-danger-outline"
                            @click="confirmDisconnect = true"
                            :disabled="disconnecting"
                        >
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18.36 6.64a9 9 0 11-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/></svg>
                            Disconnect
                        </button>
                        <button class="rp-btn rp-btn-secondary-outline">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M23 4v6h-6"/><path d="M1 20v-6h6"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
                            Reconnect
                        </button>
                    </div>
                </div>

                <!-- ── API Credentials (editable) ── -->
                <div class="rp-card">
                    <div class="rp-card-header">
                        <div class="rp-card-icon rp-card-icon--purple">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 8h-1V6a7 7 0 00-14 0v2H2a2 2 0 00-2 2v8a2 2 0 002 2h16a2 2 0 002-2v-8a2 2 0 00-2-2zM7 6a3 3 0 016 0v2H7V6z" clip-rule="evenodd"/></svg>
                        </div>
                        <div>
                            <div class="rp-card-title">API Credentials</div>
                            <div class="rp-card-desc">Your Reava Pay merchant credentials. Leave blank to use platform credentials.</div>
                        </div>
                    </div>

                    <div v-if="credentials.has_platform_credentials" class="rp-info-box rp-info-box--cyan rp-mb-4">
                        <svg width="15" height="15" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                        Platform credentials are available. Your own credentials are optional and will take priority if provided.
                    </div>

                    <div class="rp-form-grid">
                        <div class="rp-form-group">
                            <label class="rp-label">API Key</label>
                            <input
                                v-model="form.api_key"
                                type="text"
                                class="rp-input"
                                :class="{ 'rp-input-error': form.errors.api_key }"
                                placeholder="rp_live_••••••••••••"
                                autocomplete="off"
                            />
                            <p v-if="form.errors.api_key" class="rp-error-text">{{ form.errors.api_key }}</p>
                        </div>

                        <div class="rp-form-group">
                            <label class="rp-label">Public Key</label>
                            <input
                                v-model="form.public_key"
                                type="text"
                                class="rp-input"
                                placeholder="rp_pk_••••••••••••"
                                autocomplete="off"
                            />
                        </div>

                        <div class="rp-form-group rp-form-group--full">
                            <label class="rp-label">API Secret</label>
                            <div class="rp-input-group">
                                <input
                                    v-model="form.api_secret"
                                    :type="show.apiSecret ? 'text' : 'password'"
                                    class="rp-input"
                                    :class="{ 'rp-input-error': form.errors.api_secret }"
                                    placeholder="Leave blank to keep existing"
                                    autocomplete="new-password"
                                />
                                <button type="button" class="rp-input-addon" @click="show.apiSecret = !show.apiSecret">
                                    <svg v-if="!show.apiSecret" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg v-else width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                </button>
                            </div>
                            <p class="rp-hint">Leave blank to keep existing</p>
                        </div>

                        <div class="rp-form-group rp-form-group--full">
                            <label class="rp-label">Webhook Secret</label>
                            <div class="rp-input-group">
                                <input
                                    v-model="form.webhook_secret"
                                    :type="show.webhookSecret ? 'text' : 'password'"
                                    class="rp-input"
                                    placeholder="Leave blank to keep existing"
                                    autocomplete="new-password"
                                />
                                <button type="button" class="rp-input-addon" @click="show.webhookSecret = !show.webhookSecret">
                                    <svg v-if="!show.webhookSecret" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg v-else width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Payment Channels ── -->
                <div class="rp-card">
                    <div class="rp-card-header">
                        <div class="rp-card-icon rp-card-icon--green">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/><path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/></svg>
                        </div>
                        <div>
                            <div class="rp-card-title">Payment Channels</div>
                            <div class="rp-card-desc">Choose which payment methods to accept from your tenants</div>
                        </div>
                        <div class="rp-ml-auto">
                            <span class="rp-channel-count">{{ activeChannelsCount }}/{{ paymentChannels.length }} active</span>
                        </div>
                    </div>

                    <div class="rp-channels-grid">
                        <div
                            v-for="ch in paymentChannels"
                            :key="ch.key"
                            class="rp-channel-card"
                            :class="{ 'rp-channel-card--active': form.channels[ch.key] }"
                            :style="form.channels[ch.key] ? `--ch-color: ${ch.color}; --ch-bg: ${ch.bgColor}; --ch-border: ${ch.borderColor}` : ''"
                            @click="form.channels[ch.key] = !form.channels[ch.key]"
                        >
                            <div class="rp-channel-icon" :style="form.channels[ch.key] ? `background: ${ch.bgColor}` : ''">
                                <span class="rp-channel-emoji">{{ ch.icon }}</span>
                            </div>
                            <div class="rp-channel-info">
                                <div class="rp-channel-name">{{ ch.name }}</div>
                                <div class="rp-channel-sub">{{ ch.subtitle }}</div>
                                <div class="rp-channel-desc">{{ ch.description }}</div>
                            </div>
                            <div class="rp-channel-toggle-wrap">
                                <button
                                    type="button"
                                    class="rp-toggle"
                                    :class="{ 'rp-toggle--on': form.channels[ch.key] }"
                                    @click.stop="form.channels[ch.key] = !form.channels[ch.key]"
                                    :aria-label="`Toggle ${ch.name}`"
                                >
                                    <span class="rp-toggle-knob"></span>
                                </button>
                                <div class="rp-channel-status-label" :class="form.channels[ch.key] ? 'rp-status-on' : 'rp-status-off'">
                                    {{ form.channels[ch.key] ? 'Active' : 'Inactive' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Wallet & Settlement ── -->
                <div class="rp-card">
                    <div class="rp-card-header">
                        <div class="rp-card-icon rp-card-icon--amber">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm2 3a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm0 3a1 1 0 000 2h2a1 1 0 100-2H6z"/></svg>
                        </div>
                        <div>
                            <div class="rp-card-title">Wallet &amp; Settlement</div>
                            <div class="rp-card-desc">Configure how collected funds are managed and settled</div>
                        </div>
                    </div>

                    <div class="rp-toggle-list">
                        <div class="rp-toggle-row">
                            <div class="rp-toggle-row-info">
                                <div class="rp-toggle-row-title">Auto-Credit Wallet</div>
                                <div class="rp-toggle-row-desc">Automatically credit your Gwinto wallet upon successful payment</div>
                            </div>
                            <button
                                type="button"
                                class="rp-toggle"
                                :class="{ 'rp-toggle--on': form.auto_credit }"
                                @click="form.auto_credit = !form.auto_credit"
                            >
                                <span class="rp-toggle-knob"></span>
                            </button>
                        </div>

                        <div class="rp-toggle-row">
                            <div class="rp-toggle-row-info">
                                <div class="rp-toggle-row-title">Auto-Settle</div>
                                <div class="rp-toggle-row-desc">Automatically settle funds to your linked bank account</div>
                            </div>
                            <button
                                type="button"
                                class="rp-toggle"
                                :class="{ 'rp-toggle--on': form.auto_settle }"
                                @click="form.auto_settle = !form.auto_settle"
                            >
                                <span class="rp-toggle-knob"></span>
                            </button>
                        </div>
                    </div>

                    <div class="rp-form-grid rp-mt-4">
                        <div class="rp-form-group">
                            <label class="rp-label">Settlement Schedule</label>
                            <select v-model="form.settlement_schedule" class="rp-select">
                                <option value="manual">Manual</option>
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                        <div class="rp-form-group">
                            <label class="rp-label">Minimum Settlement Amount (KES)</label>
                            <input
                                v-model="form.min_settlement_amount"
                                type="number"
                                min="0"
                                step="100"
                                class="rp-input"
                                placeholder="1000.00"
                            />
                        </div>
                    </div>
                </div>

                <!-- ── Webhook URL ── -->
                <div class="rp-card">
                    <div class="rp-card-header">
                        <div class="rp-card-icon rp-card-icon--pink">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        </div>
                        <div>
                            <div class="rp-card-title">Webhook URL</div>
                            <div class="rp-card-desc">Set this URL in your Reava Pay dashboard to receive payment notifications</div>
                        </div>
                    </div>

                    <div class="rp-webhook-url-box">
                        <div class="rp-webhook-url-inner">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:#6b7280;flex-shrink:0"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
                            <span class="rp-webhook-url-text">{{ webhookUrl }}</span>
                        </div>
                        <button class="rp-btn rp-btn-copy" @click="copyToClipboard(webhookUrl, 'webhook')">
                            <svg v-if="!copied['webhook']" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                            <svg v-else width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
                            {{ copied['webhook'] ? 'Copied!' : 'Copy URL' }}
                        </button>
                    </div>
                    <p class="rp-hint rp-mt-2">Add this URL to your Reava Pay dashboard → Settings → Webhooks</p>
                </div>

                <!-- ── Save / Enable actions ── -->
                <div class="rp-actions-bar">
                    <button
                        v-if="!isConnected"
                        type="button"
                        class="rp-btn rp-btn-enable"
                        @click="enable"
                        :disabled="enabling"
                    >
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18.36 6.64a9 9 0 11-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/></svg>
                        {{ enabling ? 'Enabling…' : 'Enable Reava Pay' }}
                    </button>
                    <button
                        type="button"
                        class="rp-btn rp-btn-save"
                        @click="saveSettings"
                        :disabled="form.processing"
                    >
                        <svg v-if="!form.processing" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        <svg v-else width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="rp-spin"><path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path d="M9 12l2 2 4-4"/></svg>
                        {{ form.processing ? 'Saving…' : 'Save Settings' }}
                    </button>
                </div>

            </div><!-- /.rp-main -->

            <!-- ── Sidebar ── -->
            <div class="rp-sidebar">

                <!-- Quick Actions -->
                <div class="rp-card">
                    <div class="rp-card-header rp-card-header--compact">
                        <div class="rp-card-icon rp-card-icon--indigo">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/></svg>
                        </div>
                        <div class="rp-card-title">Quick Actions</div>
                    </div>

                    <div class="rp-quick-actions">
                        <button
                            class="rp-quick-btn"
                            @click="testConnection"
                            :disabled="testingConnection"
                        >
                            <div class="rp-quick-btn-icon" :class="testingConnection ? 'rp-spin' : ''">
                                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 6l7 7 4-4 7 7"/></svg>
                            </div>
                            <span>{{ testingConnection ? 'Testing…' : 'Test Connection' }}</span>
                        </button>

                        <!-- Connection test result -->
                        <transition name="fade">
                            <div v-if="testResult" class="rp-test-result" :class="testResult.success ? 'rp-test-result--success' : 'rp-test-result--fail'">
                                <svg v-if="testResult.success" width="14" height="14" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                <svg v-else width="14" height="14" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                <div>
                                    <div class="rp-test-result-title">{{ testResult.success ? 'Connected' : 'Failed' }}</div>
                                    <div v-if="testResult.success" class="rp-test-result-meta">Balance: {{ fmt(testResult.balance, testResult.currency) }}</div>
                                    <div v-else class="rp-test-result-meta">{{ testResult.message }}</div>
                                </div>
                            </div>
                        </transition>

                        <a href="#" class="rp-quick-btn">
                            <div class="rp-quick-btn-icon">
                                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                            </div>
                            <span>View All Transactions</span>
                        </a>
                    </div>

                    <div class="rp-divider"></div>

                    <div class="rp-meta-list">
                        <div class="rp-meta-row">
                            <span class="rp-meta-key">Credentials</span>
                            <span class="rp-badge" :class="credentials.has_own_credentials ? 'rp-badge--blue' : 'rp-badge--gray'">
                                {{ credentials.has_own_credentials ? 'Own' : 'Platform' }}
                            </span>
                        </div>
                        <div class="rp-meta-row">
                            <span class="rp-meta-key">Verified</span>
                            <span class="rp-badge" :class="credentials.verified ? 'rp-badge--green' : 'rp-badge--yellow'">
                                {{ credentials.verified ? 'Yes' : 'Pending' }}
                            </span>
                        </div>
                        <div class="rp-meta-row">
                            <span class="rp-meta-key">Environment</span>
                            <span class="rp-badge" :class="environment === 'live' ? 'rp-badge--green' : 'rp-badge--yellow'">
                                {{ environment === 'live' ? 'Production' : 'Sandbox' }}
                            </span>
                        </div>
                        <div v-if="credentials.last_verified" class="rp-meta-row">
                            <span class="rp-meta-key">Last Verified</span>
                            <span class="rp-meta-val">{{ credentials.last_verified }}</span>
                        </div>
                    </div>
                </div>

                <!-- Integration Status -->
                <div class="rp-card rp-status-card" :class="isConnected ? 'rp-status-card--ok' : 'rp-status-card--warn'">
                    <div class="rp-status-icon">
                        <svg v-if="isConnected" width="22" height="22" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <svg v-else width="22" height="22" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    </div>
                    <div class="rp-status-text">
                        <div class="rp-status-title">{{ isConnected ? 'Integration Active' : 'Not Connected' }}</div>
                        <div class="rp-status-desc">
                            {{ isConnected
                                ? 'Your Reava Pay integration is live and processing payments.'
                                : 'Configure your credentials and click Enable to get started.' }}
                        </div>
                    </div>
                </div>

            </div><!-- /.rp-sidebar -->
        </div><!-- /.rp-layout -->

        <!-- ── Disconnect confirmation modal ── -->
        <transition name="modal">
            <div v-if="confirmDisconnect" class="rp-modal-overlay" @click.self="confirmDisconnect = false">
                <div class="rp-modal">
                    <div class="rp-modal-icon">
                        <svg width="28" height="28" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    </div>
                    <h3 class="rp-modal-title">Disconnect Reava Pay?</h3>
                    <p class="rp-modal-body">This will disable all payment processing. Existing transactions will not be affected. You can reconnect at any time.</p>
                    <div class="rp-modal-actions">
                        <button class="rp-btn rp-btn-ghost" @click="confirmDisconnect = false">Cancel</button>
                        <button class="rp-btn rp-btn-danger" @click="disconnect" :disabled="disconnecting">
                            {{ disconnecting ? 'Disconnecting…' : 'Yes, Disconnect' }}
                        </button>
                    </div>
                </div>
            </div>
        </transition>

    </div>
</template>

<style scoped>
/* ── Root & Layout ── */
.rp-settings-page { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #111827; }

.rp-layout {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 20px;
    align-items: start;
    padding: 20px;
}
@media (max-width: 900px) {
    .rp-layout { grid-template-columns: 1fr; padding: 12px; }
    .rp-sidebar { order: -1; }
}

.rp-main { display: flex; flex-direction: column; gap: 20px; }
.rp-sidebar { display: flex; flex-direction: column; gap: 16px; position: sticky; top: 16px; }
@media (max-width: 900px) { .rp-sidebar { position: static; } }

/* ── Hero ── */
.rp-hero {
    background: linear-gradient(135deg, #0f766e 0%, #0891b2 60%, #1d4ed8 100%);
    padding: 24px 24px 0;
    border-radius: 0;
    color: white;
    margin-bottom: 0;
}
.rp-hero-inner { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; }
.rp-hero-brand { display: flex; align-items: center; gap: 14px; }
.rp-hero-logo { background: rgba(255,255,255,0.15); border-radius: 12px; padding: 8px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
.rp-hero-title { font-size: 1.2rem; font-weight: 700; line-height: 1.2; }
.rp-hero-subtitle { font-size: 0.82rem; opacity: 0.8; margin-top: 3px; }

.rp-hero-badge {
    display: flex; align-items: center; gap: 7px;
    padding: 6px 14px; border-radius: 999px; font-size: 0.8rem; font-weight: 600;
    backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.3);
}
.rp-hero-badge--connected { background: rgba(22,163,74,0.25); }
.rp-hero-badge--disconnected { background: rgba(220,38,38,0.25); }

.rp-pulse-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.rp-pulse-dot--green { background: #4ade80; box-shadow: 0 0 0 3px rgba(74,222,128,0.3); animation: pulse 2s infinite; }
.rp-pulse-dot--red { background: #f87171; }
@keyframes pulse { 0%,100% { box-shadow: 0 0 0 3px rgba(74,222,128,0.3); } 50% { box-shadow: 0 0 0 6px rgba(74,222,128,0.1); } }

.rp-hero-stats {
    display: flex; align-items: stretch;
    background: rgba(0,0,0,0.15); border-radius: 12px 12px 0 0;
    margin: 0 -24px; padding: 0 24px;
    overflow-x: auto; -webkit-overflow-scrolling: touch;
}
.rp-hero-stat { padding: 16px 20px; text-align: center; flex: 1; min-width: 100px; }
.rp-hero-stat-value { font-size: 1.1rem; font-weight: 700; white-space: nowrap; }
.rp-hero-stat-label { font-size: 0.72rem; opacity: 0.75; margin-top: 2px; white-space: nowrap; }
.rp-hero-stat-divider { width: 1px; background: rgba(255,255,255,0.15); margin: 12px 0; }

/* ── Cards ── */
.rp-card {
    background: #fff; border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.06);
    border: 1px solid #f3f4f6;
    padding: 20px;
}
.rp-card-header {
    display: flex; align-items: flex-start; gap: 12px;
    margin-bottom: 20px; flex-wrap: wrap;
}
.rp-card-header--compact { margin-bottom: 14px; }
.rp-card-icon {
    width: 36px; height: 36px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.rp-card-icon--blue { background: #eff6ff; color: #1d4ed8; }
.rp-card-icon--purple { background: #f5f3ff; color: #7c3aed; }
.rp-card-icon--green { background: #f0fdf4; color: #16a34a; }
.rp-card-icon--amber { background: #fffbeb; color: #d97706; }
.rp-card-icon--pink { background: #fdf2f8; color: #db2777; }
.rp-card-icon--indigo { background: #eef2ff; color: #4f46e5; }
.rp-card-title { font-size: 0.95rem; font-weight: 700; color: #111827; }
.rp-card-desc { font-size: 0.78rem; color: #6b7280; margin-top: 2px; }
.rp-ml-auto { margin-left: auto; }

/* ── Credentials display ── */
.rp-credentials-grid {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 0; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden;
}
@media (max-width: 580px) { .rp-credentials-grid { grid-template-columns: 1fr; } }

.rp-cred-field {
    padding: 14px 16px; border-right: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb;
    background: #f9fafb;
}
.rp-cred-field:nth-child(even) { border-right: none; }
.rp-cred-field--full { grid-column: 1 / -1; border-right: none; }
.rp-cred-field:last-child { border-bottom: none; }
.rp-cred-field:nth-last-child(2):not(.rp-cred-field--full) { border-bottom: none; }

.rp-cred-label { font-size: 0.72rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 6px; display: block; }
.rp-cred-value-row { display: flex; align-items: center; gap: 8px; }
.rp-cred-value { font-size: 0.88rem; color: #111827; font-weight: 500; flex: 1; word-break: break-all; }
.rp-cred-monospace { font-family: 'SF Mono', 'Fira Code', monospace; font-size: 0.82rem; }
.rp-cred-truncate { word-break: break-all; }
.rp-icon-btn {
    padding: 5px; border-radius: 6px; border: none; background: transparent;
    cursor: pointer; color: #9ca3af; transition: color 0.15s, background 0.15s;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.rp-icon-btn:hover { background: #f3f4f6; color: #374151; }

.rp-link-small {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 0.75rem; color: #2563eb; margin-top: 6px; text-decoration: none;
}
.rp-link-small:hover { text-decoration: underline; }

.rp-connected-meta {
    display: flex; align-items: center; gap: 6px;
    font-size: 0.78rem; color: #6b7280; padding: 10px 0;
    border-top: 1px solid #f3f4f6; margin-top: 16px;
}
.rp-cred-actions { display: flex; gap: 10px; margin-top: 16px; flex-wrap: wrap; }

/* ── Env badge ── */
.rp-env-badge {
    font-size: 0.72rem; font-weight: 700; padding: 3px 10px; border-radius: 999px;
    letter-spacing: 0.03em; display: inline-block;
}
.rp-env-badge--live { background: #dcfce7; color: #15803d; }
.rp-env-badge--test { background: #fef3c7; color: #92400e; }

/* ── Info box ── */
.rp-info-box {
    display: flex; align-items: flex-start; gap: 8px;
    padding: 10px 14px; border-radius: 8px; font-size: 0.78rem; line-height: 1.5;
    margin-top: 14px;
}
.rp-info-box--blue { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
.rp-info-box--cyan { background: #ecfeff; color: #0e7490; border: 1px solid #a5f3fc; }
.rp-mb-4 { margin-bottom: 16px; }
.rp-mt-4 { margin-top: 16px; }
.rp-mt-2 { margin-top: 8px; }

/* ── Payment Channels ── */
.rp-channel-count { font-size: 0.78rem; color: #6b7280; font-weight: 500; white-space: nowrap; }

.rp-channels-grid { display: flex; flex-direction: column; gap: 10px; }

.rp-channel-card {
    display: flex; align-items: center; gap: 14px;
    padding: 16px; border-radius: 12px;
    border: 2px solid #e5e7eb; background: #f9fafb;
    cursor: pointer; transition: all 0.2s;
    -webkit-tap-highlight-color: transparent; user-select: none;
}
.rp-channel-card:hover { border-color: #d1d5db; background: #fff; }
.rp-channel-card--active {
    border-color: var(--ch-border, #0f766e);
    background: var(--ch-bg, #f0fdfa);
    box-shadow: 0 0 0 3px rgba(15,118,110,0.08);
}

.rp-channel-icon {
    width: 48px; height: 48px; border-radius: 12px;
    background: #f3f4f6; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; transition: background 0.2s;
}
.rp-channel-emoji { font-size: 1.4rem; line-height: 1; }
.rp-channel-info { flex: 1; min-width: 0; }
.rp-channel-name { font-size: 0.92rem; font-weight: 700; color: #111827; }
.rp-channel-sub { font-size: 0.75rem; color: #6b7280; margin-top: 1px; }
.rp-channel-desc { font-size: 0.75rem; color: #9ca3af; margin-top: 2px; }

.rp-channel-toggle-wrap { display: flex; flex-direction: column; align-items: center; gap: 4px; flex-shrink: 0; }
.rp-channel-status-label { font-size: 0.68rem; font-weight: 600; letter-spacing: 0.03em; }
.rp-status-on { color: #16a34a; }
.rp-status-off { color: #9ca3af; }

/* ── Toggle switch ── */
.rp-toggle {
    width: 44px; height: 24px; border-radius: 999px; border: none; cursor: pointer;
    background: #d1d5db; position: relative; transition: background 0.2s; flex-shrink: 0;
    padding: 0;
}
.rp-toggle--on { background: #0f766e; }
.rp-toggle-knob {
    position: absolute; top: 3px; left: 3px;
    width: 18px; height: 18px; border-radius: 50%; background: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2); transition: transform 0.2s;
}
.rp-toggle--on .rp-toggle-knob { transform: translateX(20px); }

/* ── Toggle list (Wallet section) ── */
.rp-toggle-list { display: flex; flex-direction: column; gap: 0; }
.rp-toggle-row {
    display: flex; align-items: center; gap: 16px;
    padding: 14px 0; border-bottom: 1px solid #f3f4f6;
}
.rp-toggle-row:last-child { border-bottom: none; }
.rp-toggle-row-info { flex: 1; }
.rp-toggle-row-title { font-size: 0.88rem; font-weight: 600; color: #111827; }
.rp-toggle-row-desc { font-size: 0.75rem; color: #6b7280; margin-top: 2px; }

/* ── Forms ── */
.rp-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 580px) { .rp-form-grid { grid-template-columns: 1fr; } }
.rp-form-group { display: flex; flex-direction: column; gap: 5px; }
.rp-form-group--full { grid-column: 1 / -1; }
.rp-label { font-size: 0.78rem; font-weight: 600; color: #374151; }
.rp-input {
    padding: 9px 12px; border: 1.5px solid #e5e7eb; border-radius: 8px;
    font-size: 0.85rem; color: #111827; background: #fff; width: 100%; box-sizing: border-box;
    transition: border-color 0.15s, box-shadow 0.15s; outline: none;
}
.rp-input:focus { border-color: #0f766e; box-shadow: 0 0 0 3px rgba(15,118,110,0.1); }
.rp-input-error { border-color: #dc2626 !important; }
.rp-error-text { font-size: 0.73rem; color: #dc2626; }
.rp-hint { font-size: 0.73rem; color: #9ca3af; }
.rp-select {
    padding: 9px 12px; border: 1.5px solid #e5e7eb; border-radius: 8px;
    font-size: 0.85rem; color: #111827; background: #fff; width: 100%;
    appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 12px center; cursor: pointer;
    outline: none; transition: border-color 0.15s;
}
.rp-select:focus { border-color: #0f766e; box-shadow: 0 0 0 3px rgba(15,118,110,0.1); }

.rp-input-group { position: relative; display: flex; }
.rp-input-group .rp-input { padding-right: 44px; }
.rp-input-addon {
    position: absolute; right: 0; top: 0; bottom: 0; width: 40px;
    display: flex; align-items: center; justify-content: center;
    background: transparent; border: none; cursor: pointer; color: #9ca3af;
    transition: color 0.15s;
}
.rp-input-addon:hover { color: #374151; }

/* ── Webhook URL ── */
.rp-webhook-url-box {
    display: flex; align-items: center; gap: 10px;
    background: #f9fafb; border: 1.5px solid #e5e7eb; border-radius: 10px;
    padding: 12px 14px; flex-wrap: wrap;
}
.rp-webhook-url-inner { display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0; }
.rp-webhook-url-text { font-family: 'SF Mono', 'Fira Code', monospace; font-size: 0.8rem; color: #2563eb; word-break: break-all; }

/* ── Buttons ── */
.rp-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 18px; border-radius: 9px; font-size: 0.85rem; font-weight: 600;
    cursor: pointer; border: 2px solid transparent; transition: all 0.15s; text-decoration: none;
}
.rp-btn:disabled { opacity: 0.6; cursor: not-allowed; }

.rp-btn-danger-outline { border-color: #fca5a5; color: #dc2626; background: #fff; }
.rp-btn-danger-outline:hover:not(:disabled) { background: #fef2f2; border-color: #dc2626; }
.rp-btn-secondary-outline { border-color: #d1d5db; color: #374151; background: #fff; }
.rp-btn-secondary-outline:hover:not(:disabled) { background: #f9fafb; border-color: #9ca3af; }
.rp-btn-copy { border-color: #e5e7eb; color: #374151; background: #fff; font-size: 0.78rem; padding: 7px 12px; flex-shrink: 0; }
.rp-btn-copy:hover { background: #f9fafb; border-color: #9ca3af; }
.rp-btn-ghost { border-color: #e5e7eb; color: #374151; background: #fff; }
.rp-btn-ghost:hover { background: #f9fafb; }
.rp-btn-danger { background: #dc2626; color: #fff; border-color: #dc2626; }
.rp-btn-danger:hover:not(:disabled) { background: #b91c1c; }

.rp-btn-enable {
    flex: 1; justify-content: center;
    background: linear-gradient(135deg, #0f766e, #0891b2);
    color: #fff; border: none; padding: 12px 24px; font-size: 0.9rem;
    box-shadow: 0 2px 8px rgba(15,118,110,0.3);
}
.rp-btn-enable:hover:not(:disabled) { background: linear-gradient(135deg, #0d6460, #0781a0); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(15,118,110,0.4); }

.rp-btn-save {
    flex: 1; justify-content: center;
    background: linear-gradient(135deg, #1d4ed8, #2563eb);
    color: #fff; border: none; padding: 12px 24px; font-size: 0.9rem;
    box-shadow: 0 2px 8px rgba(29,78,216,0.3);
}
.rp-btn-save:hover:not(:disabled) { background: linear-gradient(135deg, #1e40af, #1d4ed8); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(29,78,216,0.4); }

.rp-actions-bar {
    display: flex; gap: 12px; flex-wrap: wrap;
    background: #fff; border-radius: 14px;
    border: 1px solid #f3f4f6; padding: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}

/* ── Quick Actions sidebar ── */
.rp-quick-actions { display: flex; flex-direction: column; gap: 8px; margin-bottom: 4px; }
.rp-quick-btn {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 14px; border-radius: 9px;
    border: 1.5px solid #e5e7eb; background: #fff;
    font-size: 0.82rem; font-weight: 500; color: #374151;
    cursor: pointer; transition: all 0.15s; text-decoration: none;
    width: 100%;
}
.rp-quick-btn:hover:not(:disabled) { background: #f9fafb; border-color: #9ca3af; }
.rp-quick-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.rp-quick-btn-icon {
    width: 28px; height: 28px; border-radius: 7px; background: #f3f4f6;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    color: #374151;
}

.rp-test-result {
    display: flex; align-items: flex-start; gap: 8px;
    padding: 10px 12px; border-radius: 8px; font-size: 0.78rem;
}
.rp-test-result--success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
.rp-test-result--fail { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.rp-test-result-title { font-weight: 700; }
.rp-test-result-meta { opacity: 0.85; margin-top: 1px; }

.rp-divider { height: 1px; background: #f3f4f6; margin: 12px -20px; }
.rp-meta-list { display: flex; flex-direction: column; gap: 8px; padding-top: 4px; }
.rp-meta-row { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.rp-meta-key { font-size: 0.78rem; color: #6b7280; font-weight: 500; }
.rp-meta-val { font-size: 0.78rem; color: #374151; }

.rp-badge {
    font-size: 0.68rem; font-weight: 700; padding: 2px 8px; border-radius: 999px;
    letter-spacing: 0.02em; display: inline-block;
}
.rp-badge--blue { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.rp-badge--gray { background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb; }
.rp-badge--green { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.rp-badge--yellow { background: #fef9c3; color: #854d0e; border: 1px solid #fde047; }

/* ── Status card ── */
.rp-status-card { display: flex; gap: 12px; align-items: flex-start; }
.rp-status-card--ok { background: #f0fdf4; border-color: #bbf7d0; }
.rp-status-card--warn { background: #fffbeb; border-color: #fde68a; }
.rp-status-icon { flex-shrink: 0; margin-top: 1px; }
.rp-status-card--ok .rp-status-icon { color: #16a34a; }
.rp-status-card--warn .rp-status-icon { color: #d97706; }
.rp-status-title { font-size: 0.88rem; font-weight: 700; color: #111827; }
.rp-status-desc { font-size: 0.75rem; color: #6b7280; margin-top: 3px; line-height: 1.4; }

/* ── Alert flash ── */
.rp-alert {
    display: flex; align-items: center; gap: 8px;
    padding: 12px 20px; font-size: 0.85rem; font-weight: 500;
    margin-bottom: 0;
}
.rp-alert-success { background: #f0fdf4; color: #15803d; border-bottom: 2px solid #bbf7d0; }
.rp-alert-error { background: #fef2f2; color: #dc2626; border-bottom: 2px solid #fecaca; }

/* ── Modal ── */
.rp-modal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,0.5);
    display: flex; align-items: center; justify-content: center;
    z-index: 9999; padding: 16px;
}
.rp-modal {
    background: #fff; border-radius: 16px; padding: 28px;
    width: 100%; max-width: 400px; text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
}
.rp-modal-icon { color: #dc2626; margin-bottom: 12px; display: flex; justify-content: center; }
.rp-modal-title { font-size: 1.05rem; font-weight: 700; color: #111827; margin-bottom: 8px; }
.rp-modal-body { font-size: 0.82rem; color: #6b7280; line-height: 1.6; margin-bottom: 20px; }
.rp-modal-actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }

/* ── Spin animation ── */
.rp-spin { animation: spin 1s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

/* ── Transitions ── */
.slide-down-enter-active, .slide-down-leave-active { transition: all 0.25s ease; max-height: 60px; overflow: hidden; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; max-height: 0; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-active .rp-modal, .modal-leave-active .rp-modal { transition: transform 0.2s; }
.modal-enter-from .rp-modal, .modal-leave-to .rp-modal { transform: scale(0.95) translateY(10px); }
</style>
