@extends('layouts.admin')

@section('title', 'Reava Pay Settings - Gwinto Admin')

@push('styles')
<style>
/* Reava Pay Header */
.rp-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 40%, #0ea5e9 100%);
    border-radius: 20px;
    padding: 2rem 2.5rem;
    position: relative;
    overflow: hidden;
    color: white;
    margin-bottom: 2rem;
}
.rp-header::before {
    content: '';
    position: absolute;
    top: -60%;
    right: -15%;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(14, 165, 233, 0.2) 0%, transparent 70%);
    border-radius: 50%;
}
.rp-header::after {
    content: '';
    position: absolute;
    bottom: -40%;
    left: 5%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, transparent 70%);
    border-radius: 50%;
}
.rp-header .rp-logo {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, #0ea5e9, #6366f1);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    box-shadow: 0 8px 32px rgba(14, 165, 233, 0.4);
}
.rp-header .badge-env {
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
    padding: 0.35rem 1rem;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.rp-header .badge-env.production { background: rgba(16, 185, 129, 0.3); border-color: rgba(16, 185, 129, 0.5); }
.rp-header .badge-env.sandbox { background: rgba(251, 191, 36, 0.3); border-color: rgba(251, 191, 36, 0.5); }

/* Stats Grid */
.rp-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1.5rem;
}
.rp-stat-card {
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 14px;
    padding: 1.25rem;
    transition: all 0.3s ease;
}
.rp-stat-card:hover {
    background: rgba(255,255,255,0.15);
    transform: translateY(-3px);
}
.rp-stat-value { font-size: 1.75rem; font-weight: 800; line-height: 1.2; }
.rp-stat-label { font-size: 0.75rem; opacity: 0.7; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 0.25rem; }

/* Settings Cards */
.settings-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.settings-card:hover {
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
}
.settings-card-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: linear-gradient(to right, #fafbfc, white);
}
.settings-card-header .icon-box {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}
.settings-card-header h6 {
    font-weight: 700;
    font-size: 1rem;
    color: #1e293b;
    margin: 0;
}
.settings-card-body { padding: 1.5rem; }

/* Connection Status */
.connection-status {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-size: 0.8rem;
    font-weight: 600;
}
.connection-status.connected {
    background: #ecfdf5;
    color: #059669;
    border: 1px solid #a7f3d0;
}
.connection-status.disconnected {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
}
.connection-status .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    animation: pulse 2s infinite;
}
.connection-status.connected .dot { background: #10b981; }
.connection-status.disconnected .dot { background: #ef4444; }
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* Toggle Switch */
.rp-toggle {
    position: relative;
    width: 52px;
    height: 28px;
}
.rp-toggle input { opacity: 0; width: 0; height: 0; }
.rp-toggle .slider {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background: #cbd5e1;
    border-radius: 28px;
    transition: 0.3s;
}
.rp-toggle .slider:before {
    position: absolute;
    content: "";
    height: 22px;
    width: 22px;
    left: 3px;
    bottom: 3px;
    background: white;
    border-radius: 50%;
    transition: 0.3s;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
.rp-toggle input:checked + .slider { background: #0ea5e9; }
.rp-toggle input:checked + .slider:before { transform: translateX(24px); }

/* Channel Card */
.channel-card {
    border: 2px solid #e2e8f0;
    border-radius: 14px;
    padding: 1.25rem;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
}
.channel-card:hover { border-color: #0ea5e9; }
.channel-card.active { border-color: #0ea5e9; background: #f0f9ff; }
.channel-card .channel-icon {
    width: 50px;
    height: 50px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin: 0 auto 0.75rem;
}

/* Company List */
.company-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.2s;
}
.company-item:hover { background: #f8fafc; }
.company-item:last-child { border-bottom: none; }

/* Transaction Row */
.txn-row {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.875rem;
    transition: background 0.2s;
}
.txn-row:hover { background: #f8fafc; }
.txn-row:last-child { border-bottom: none; }

/* Form Styles */
.form-label-rp {
    font-weight: 600;
    font-size: 0.85rem;
    color: #475569;
    margin-bottom: 0.4rem;
}
.form-control-rp {
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    padding: 0.65rem 1rem;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}
.form-control-rp:focus {
    border-color: #0ea5e9;
    box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15);
}

/* Tabs */
.rp-tabs {
    display: flex;
    gap: 0.25rem;
    padding: 0.25rem;
    background: #f1f5f9;
    border-radius: 12px;
    margin-bottom: 1.5rem;
}
.rp-tab {
    padding: 0.6rem 1.25rem;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #64748b;
    border: none;
    background: transparent;
    cursor: pointer;
    transition: all 0.3s ease;
}
.rp-tab:hover { color: #1e293b; }
.rp-tab.active {
    background: white;
    color: #0ea5e9;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

/* Button */
.btn-reava {
    background: linear-gradient(135deg, #0ea5e9, #6366f1);
    color: white;
    border: none;
    padding: 0.65rem 1.5rem;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}
.btn-reava:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(14, 165, 233, 0.4);
    color: white;
}
.btn-reava-outline {
    background: transparent;
    color: #0ea5e9;
    border: 2px solid #0ea5e9;
    padding: 0.55rem 1.25rem;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.3s ease;
}
.btn-reava-outline:hover {
    background: #0ea5e9;
    color: white;
}
</style>
@endpush

@section('content')
<div class="container-fluid">

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 12px; border: none; background: linear-gradient(135deg, #ecfdf5, #d1fae5);">
        <i class="bi bi-check-circle-fill me-2 text-success"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 12px; border: none; background: linear-gradient(135deg, #fef2f2, #fee2e2);">
        <i class="bi bi-exclamation-triangle-fill me-2 text-danger"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Hero Header -->
    <div class="rp-header">
        <div class="d-flex justify-content-between align-items-start position-relative" style="z-index: 1;">
            <div class="d-flex align-items-center gap-3">
                <div class="rp-logo">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <div>
                    <h3 class="mb-1 fw-bold">Reava Pay Gateway</h3>
                    <p class="mb-0 opacity-75">Manage payment gateway integration & credentials</p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge-env {{ $settings->environment }}">
                    <i class="bi bi-{{ $settings->environment === 'production' ? 'globe' : 'bug' }} me-1"></i>
                    {{ ucfirst($settings->environment) }}
                </span>
                <div class="connection-status {{ $settings->is_verified ? 'connected' : 'disconnected' }}">
                    <span class="dot"></span>
                    {{ $settings->is_verified ? 'Connected' : 'Not Connected' }}
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="rp-stats-grid position-relative" style="z-index: 1;">
            <div class="rp-stat-card">
                <div class="rp-stat-value">{{ number_format($stats['total_transactions']) }}</div>
                <div class="rp-stat-label">Total Transactions</div>
            </div>
            <div class="rp-stat-card">
                <div class="rp-stat-value">KES {{ number_format($stats['total_volume'], 0) }}</div>
                <div class="rp-stat-label">Total Volume</div>
            </div>
            <div class="rp-stat-card">
                <div class="rp-stat-value">KES {{ number_format($stats['this_month_volume'], 0) }}</div>
                <div class="rp-stat-label">This Month</div>
            </div>
            <div class="rp-stat-card">
                <div class="rp-stat-value">{{ $stats['active_companies'] }}</div>
                <div class="rp-stat-label">Active Companies</div>
            </div>
            <div class="rp-stat-card">
                <div class="rp-stat-value">{{ number_format($stats['completed_transactions']) }}</div>
                <div class="rp-stat-label">Successful</div>
            </div>
            <div class="rp-stat-card">
                <div class="rp-stat-value">{{ number_format($stats['failed_transactions']) }}</div>
                <div class="rp-stat-label">Failed</div>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="rp-tabs">
        <button class="rp-tab active" onclick="switchTab('credentials', this)"><i class="bi bi-key me-1"></i> Credentials</button>
        <button class="rp-tab" onclick="switchTab('channels', this)"><i class="bi bi-grid-3x3-gap me-1"></i> Channels</button>
        <button class="rp-tab" onclick="switchTab('companies', this)"><i class="bi bi-building me-1"></i> Companies</button>
        <button class="rp-tab" onclick="switchTab('transactions', this)"><i class="bi bi-arrow-left-right me-1"></i> Transactions</button>
        <button class="rp-tab" onclick="switchTab('advanced', this)"><i class="bi bi-gear me-1"></i> Advanced</button>
    </div>

    <form action="{{ route('admin.reava-pay.update') }}" method="POST" id="settingsForm">
        @csrf

        <!-- Credentials Tab -->
        <div id="tab-credentials" class="tab-panel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <div class="icon-box" style="background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #2563eb;">
                                <i class="bi bi-key-fill"></i>
                            </div>
                            <div>
                                <h6>API Credentials</h6>
                                <small class="text-muted">Platform-level Reava Pay API keys from your merchant dashboard</small>
                            </div>
                        </div>
                        <div class="settings-card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label-rp">API Key (Public)</label>
                                    <div class="input-group">
                                        <span class="input-group-text" style="border-radius: 10px 0 0 10px; border-right: 0;"><i class="bi bi-key"></i></span>
                                        <input type="text" class="form-control form-control-rp" name="api_key" value="{{ $settings->api_key }}" placeholder="pk_live_..." style="border-radius: 0 10px 10px 0;">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-rp">Public Key (SDK)</label>
                                    <div class="input-group">
                                        <span class="input-group-text" style="border-radius: 10px 0 0 10px; border-right: 0;"><i class="bi bi-shield-lock"></i></span>
                                        <input type="text" class="form-control form-control-rp" name="public_key" value="{{ $settings->public_key }}" placeholder="pk_pub_..." style="border-radius: 0 10px 10px 0;">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-rp">API Secret</label>
                                    <div class="input-group">
                                        <span class="input-group-text" style="border-radius: 10px 0 0 10px; border-right: 0;"><i class="bi bi-lock-fill"></i></span>
                                        <input type="password" class="form-control form-control-rp" name="api_secret" placeholder="{{ $settings->api_secret_encrypted ? '••••••••••••••••' : 'sk_live_...' }}" style="border-radius: 0 10px 10px 0;">
                                    </div>
                                    <small class="text-muted">Leave blank to keep existing secret</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-rp">Webhook Secret</label>
                                    <div class="input-group">
                                        <span class="input-group-text" style="border-radius: 10px 0 0 10px; border-right: 0;"><i class="bi bi-webhook"></i></span>
                                        <input type="password" class="form-control form-control-rp" name="webhook_secret" value="{{ $settings->webhook_secret }}" placeholder="whsec_..." style="border-radius: 0 10px 10px 0;">
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label-rp">Base URL</label>
                                    <input type="url" class="form-control form-control-rp" name="base_url" value="{{ $settings->base_url }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label-rp">Environment</label>
                                    <select class="form-select form-control-rp" name="environment">
                                        <option value="sandbox" {{ $settings->environment === 'sandbox' ? 'selected' : '' }}>Sandbox (Testing)</option>
                                        <option value="production" {{ $settings->environment === 'production' ? 'selected' : '' }}>Production (Live)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="col-lg-4">
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <div class="icon-box" style="background: linear-gradient(135deg, #fef3c7, #fde68a); color: #d97706;">
                                <i class="bi bi-lightning-fill"></i>
                            </div>
                            <h6>Quick Actions</h6>
                        </div>
                        <div class="settings-card-body">
                            <div class="d-grid gap-2">
                                <form action="{{ route('admin.reava-pay.test-connection') }}" method="POST" class="d-grid">
                                    @csrf
                                    <button type="submit" class="btn btn-reava-outline">
                                        <i class="bi bi-wifi me-1"></i> Test Connection
                                    </button>
                                </form>
                                <form action="{{ route('admin.reava-pay.toggle-active') }}" method="POST" class="d-grid">
                                    @csrf
                                    <button type="submit" class="btn {{ $settings->is_active ? 'btn-outline-danger' : 'btn-reava' }}">
                                        <i class="bi bi-{{ $settings->is_active ? 'pause-circle' : 'play-circle' }} me-1"></i>
                                        {{ $settings->is_active ? 'Deactivate Gateway' : 'Activate Gateway' }}
                                    </button>
                                </form>
                            </div>

                            <hr class="my-3">

                            <div class="small">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Status</span>
                                    <span class="badge bg-{{ $settings->is_active ? 'success' : 'secondary' }}">{{ $settings->is_active ? 'Active' : 'Inactive' }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Verified</span>
                                    <span class="badge bg-{{ $settings->is_verified ? 'success' : 'warning' }}">{{ $settings->is_verified ? 'Yes' : 'No' }}</span>
                                </div>
                                @if($settings->verified_at)
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Last Verified</span>
                                    <span>{{ $settings->verified_at->diffForHumans() }}</span>
                                </div>
                                @endif
                                @if($settings->last_synced_at)
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Last Sync</span>
                                    <span>{{ $settings->last_synced_at->diffForHumans() }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Webhook URL -->
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <div class="icon-box" style="background: linear-gradient(135deg, #ede9fe, #ddd6fe); color: #7c3aed;">
                                <i class="bi bi-link-45deg"></i>
                            </div>
                            <h6>Webhook URL</h6>
                        </div>
                        <div class="settings-card-body">
                            <p class="text-muted small mb-2">Configure this URL in your Reava Pay merchant dashboard:</p>
                            <div class="bg-light rounded-3 p-3 position-relative" style="word-break: break-all;">
                                <code class="text-primary" id="webhookUrl">{{ url('webhooks/reava-pay') }}</code>
                                <button type="button" class="btn btn-sm btn-outline-primary position-absolute top-50 end-0 translate-middle-y me-2" onclick="copyWebhookUrl()" style="border-radius: 8px;">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Channels Tab -->
        <div id="tab-channels" class="tab-panel" style="display: none;">
            <div class="row">
                <div class="col-lg-8">
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <div class="icon-box" style="background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #059669;">
                                <i class="bi bi-grid-3x3-gap-fill"></i>
                            </div>
                            <h6>Payment Channels</h6>
                        </div>
                        <div class="settings-card-body">
                            <div class="row g-3">
                                <!-- M-Pesa -->
                                <div class="col-md-4">
                                    <div class="channel-card {{ $settings->mpesa_enabled ? 'active' : '' }}" onclick="toggleChannel(this, 'mpesa_enabled')">
                                        <div class="channel-icon" style="background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #059669;">
                                            <i class="bi bi-phone-fill"></i>
                                        </div>
                                        <h6 class="mb-1 fw-bold">M-Pesa</h6>
                                        <small class="text-muted">Mobile Money</small>
                                        <div class="mt-2">
                                            <label class="rp-toggle">
                                                <input type="checkbox" name="mpesa_enabled" value="1" {{ $settings->mpesa_enabled ? 'checked' : '' }}>
                                                <span class="slider"></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <!-- Card -->
                                <div class="col-md-4">
                                    <div class="channel-card {{ $settings->card_enabled ? 'active' : '' }}" onclick="toggleChannel(this, 'card_enabled')">
                                        <div class="channel-icon" style="background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #2563eb;">
                                            <i class="bi bi-credit-card-fill"></i>
                                        </div>
                                        <h6 class="mb-1 fw-bold">Card Payment</h6>
                                        <small class="text-muted">Visa / Mastercard</small>
                                        <div class="mt-2">
                                            <label class="rp-toggle">
                                                <input type="checkbox" name="card_enabled" value="1" {{ $settings->card_enabled ? 'checked' : '' }}>
                                                <span class="slider"></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <!-- Bank Transfer -->
                                <div class="col-md-4">
                                    <div class="channel-card {{ $settings->bank_transfer_enabled ? 'active' : '' }}" onclick="toggleChannel(this, 'bank_transfer_enabled')">
                                        <div class="channel-icon" style="background: linear-gradient(135deg, #fef3c7, #fde68a); color: #d97706;">
                                            <i class="bi bi-bank2"></i>
                                        </div>
                                        <h6 class="mb-1 fw-bold">Bank Transfer</h6>
                                        <small class="text-muted">Direct Bank</small>
                                        <div class="mt-2">
                                            <label class="rp-toggle">
                                                <input type="checkbox" name="bank_transfer_enabled" value="1" {{ $settings->bank_transfer_enabled ? 'checked' : '' }}>
                                                <span class="slider"></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <div class="icon-box" style="background: linear-gradient(135deg, #fce7f3, #fbcfe8); color: #db2777;">
                                <i class="bi bi-sliders"></i>
                            </div>
                            <h6>Defaults</h6>
                        </div>
                        <div class="settings-card-body">
                            <div class="mb-3">
                                <label class="form-label-rp">Default Currency</label>
                                <select class="form-select form-control-rp" name="default_currency">
                                    @foreach(['KES','UGX','TZS','NGN','GHS','ZAR','USD'] as $curr)
                                    <option value="{{ $curr }}" {{ $settings->default_currency === $curr ? 'selected' : '' }}>{{ $curr }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <label class="form-label-rp mb-0">Auto-Credit Wallet</label>
                                    <label class="rp-toggle">
                                        <input type="checkbox" name="auto_credit_wallet" value="1" {{ $settings->auto_credit_wallet ? 'checked' : '' }}>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                                <small class="text-muted">Automatically credit wallets on successful payment</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Advanced Tab -->
        <div id="tab-advanced" class="tab-panel" style="display: none;">
            <div class="settings-card">
                <div class="settings-card-header">
                    <div class="icon-box" style="background: linear-gradient(135deg, #fee2e2, #fecaca); color: #dc2626;">
                        <i class="bi bi-speedometer2"></i>
                    </div>
                    <h6>Transaction Limits</h6>
                </div>
                <div class="settings-card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label-rp">Minimum Transaction Amount ({{ $settings->default_currency }})</label>
                            <input type="number" class="form-control form-control-rp" name="min_transaction_amount" value="{{ $settings->min_transaction_amount }}" step="0.01">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-rp">Maximum Transaction Amount ({{ $settings->default_currency }})</label>
                            <input type="number" class="form-control form-control-rp" name="max_transaction_amount" value="{{ $settings->max_transaction_amount }}" step="0.01">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="d-flex justify-content-end mt-3 mb-4" id="saveBar">
            <button type="submit" class="btn btn-reava btn-lg px-5">
                <i class="bi bi-check2-circle me-2"></i> Save Settings
            </button>
        </div>
    </form>

    <!-- Companies Tab (outside form) -->
    <div id="tab-companies" class="tab-panel" style="display: none;">
        <div class="settings-card">
            <div class="settings-card-header">
                <div class="icon-box" style="background: linear-gradient(135deg, #fef3c7, #fde68a); color: #d97706;">
                    <i class="bi bi-buildings-fill"></i>
                </div>
                <div>
                    <h6>Connected Companies</h6>
                    <small class="text-muted">Companies with Reava Pay configured</small>
                </div>
            </div>
            <div class="settings-card-body p-0">
                @forelse($companySettings as $cs)
                <div class="company-item">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width: 42px; height: 42px; border-radius: 12px; background: linear-gradient(135deg, #f1f5f9, #e2e8f0); display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-building text-primary"></i>
                        </div>
                        <div>
                            <div class="fw-bold">{{ $cs->company->name ?? 'Company #' . $cs->scope_id }}</div>
                            <small class="text-muted">
                                {{ $cs->hasValidCredentials() ? 'Own credentials' : 'Platform credentials' }}
                                &middot;
                                <span class="text-{{ $cs->is_verified ? 'success' : 'warning' }}">
                                    {{ $cs->is_verified ? 'Verified' : 'Unverified' }}
                                </span>
                            </small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-{{ $cs->is_active ? 'success' : 'secondary' }} rounded-pill px-3">
                            {{ $cs->is_active ? 'Active' : 'Inactive' }}
                        </span>
                        <form action="{{ route('admin.reava-pay.company.toggle', $cs->scope_id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm {{ $cs->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}" style="border-radius: 8px;">
                                {{ $cs->is_active ? 'Disable' : 'Enable' }}
                            </button>
                        </form>
                    </div>
                </div>
                @empty
                <div class="text-center py-5">
                    <div style="width: 64px; height: 64px; background: #f1f5f9; border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                        <i class="bi bi-building text-muted" style="font-size: 1.5rem;"></i>
                    </div>
                    <h6 class="text-muted">No Companies Connected</h6>
                    <p class="text-muted small">Companies will appear here once they configure Reava Pay in their settings.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Transactions Tab (outside form) -->
    <div id="tab-transactions" class="tab-panel" style="display: none;">
        <div class="settings-card">
            <div class="settings-card-header">
                <div class="icon-box" style="background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #2563eb;">
                    <i class="bi bi-arrow-left-right"></i>
                </div>
                <div class="flex-grow-1">
                    <h6>Recent Transactions</h6>
                    <small class="text-muted">Latest 10 Reava Pay transactions across all companies</small>
                </div>
                <a href="{{ route('admin.reava-pay.transactions') }}" class="btn btn-reava-outline btn-sm">View All <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="settings-card-body p-0">
                @forelse($recentTransactions as $txn)
                <div class="txn-row">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: {{ $txn->status === 'completed' ? '#ecfdf5' : ($txn->status === 'failed' ? '#fef2f2' : '#fefce8') }}; display: flex; align-items: center; justify-content: center; margin-right: 0.75rem;">
                        <i class="bi bi-{{ $txn->channel === 'mpesa' ? 'phone' : ($txn->channel === 'card' ? 'credit-card' : 'bank') }}"
                           style="color: {{ $txn->status === 'completed' ? '#059669' : ($txn->status === 'failed' ? '#dc2626' : '#d97706') }};"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold" style="font-size: 0.85rem;">{{ $txn->gwinto_reference }}</div>
                        <small class="text-muted">{{ $txn->type_label }} &middot; {{ $txn->channel_label }} &middot; {{ $txn->company?->name ?? 'Platform' }}</small>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold" style="font-size: 0.9rem;">{{ $txn->formatted_amount }}</div>
                        <span class="badge bg-{{ $txn->status_badge }} rounded-pill" style="font-size: 0.7rem;">{{ ucfirst($txn->status) }}</span>
                    </div>
                </div>
                @empty
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                    <p class="text-muted mt-2 mb-0">No transactions yet</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function switchTab(tab, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.style.display = 'none');
    document.querySelectorAll('.rp-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + tab).style.display = 'block';
    btn.classList.add('active');

    // Hide save bar for non-form tabs
    const saveBar = document.getElementById('saveBar');
    if (['companies', 'transactions'].includes(tab)) {
        saveBar.style.display = 'none';
    } else {
        saveBar.style.display = 'flex';
    }
}

function toggleChannel(card, inputName) {
    const input = card.querySelector('input[name="' + inputName + '"]');
    input.checked = !input.checked;
    card.classList.toggle('active', input.checked);
}

function copyWebhookUrl() {
    const url = document.getElementById('webhookUrl').textContent;
    navigator.clipboard.writeText(url).then(() => {
        const btn = event.currentTarget;
        btn.innerHTML = '<i class="bi bi-check2"></i>';
        setTimeout(() => btn.innerHTML = '<i class="bi bi-clipboard"></i>', 2000);
    });
}
</script>
@endpush
