@extends('layouts.company')

@section('title', 'Reava Pay Settings - ' . $company->name)
@section('page-title', 'Reava Pay Settings')

@push('styles')
<style>
/* Credential fields — prevent horizontal overflow */
.cred-field {
    background: #f8f9fa;
    border-radius: 0.5rem;
    padding: 0.5rem 0.75rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    min-width: 0;
}
.cred-value {
    color: #1e293b;
    font-size: 0.82rem;
    word-break: break-all;
    min-width: 0;
    flex: 1;
}

/* Reava Pay Company Header */
.rp-co-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0ea5e9 100%);
    border-radius: 20px;
    padding: 2rem 2.5rem;
    color: white;
    position: relative;
    overflow: hidden;
    margin-bottom: 2rem;
    box-shadow: 0 15px 40px rgba(14, 165, 233, 0.2);
}
.rp-co-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -15%;
    width: 450px;
    height: 450px;
    background: radial-gradient(circle, rgba(14, 165, 233, 0.2) 0%, transparent 70%);
    border-radius: 50%;
}
.rp-co-logo {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, #0ea5e9, #6366f1);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    box-shadow: 0 8px 24px rgba(14, 165, 233, 0.4);
}
.rp-co-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-top: 1.25rem;
}
.rp-co-stat {
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 12px;
    padding: 1rem;
    text-align: center;
}
.rp-co-stat .value { font-size: 1.35rem; font-weight: 800; }
.rp-co-stat .label { font-size: 0.7rem; opacity: 0.7; text-transform: uppercase; letter-spacing: 0.5px; }

/* Settings Card */
.rp-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    margin-bottom: 1.5rem;
    transition: all 0.3s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.rp-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
.rp-card-head {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: linear-gradient(to right, #fafbfc, white);
}
.rp-card-head .ico {
    width: 40px;
    height: 40px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}
.rp-card-head h6 { font-weight: 700; font-size: 0.95rem; color: #1e293b; margin: 0; }
.rp-card-body { padding: 1.5rem; }

/* Channel Toggle */
.channel-opt {
    border: 2px solid #e2e8f0;
    border-radius: 14px;
    padding: 1.25rem;
    text-align: center;
    transition: all 0.3s;
    cursor: pointer;
}
.channel-opt:hover { border-color: #0ea5e9; background: #f0f9ff; }
.channel-opt.on { border-color: #0ea5e9; background: #f0f9ff; }
.channel-opt .ch-ico {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    margin: 0 auto 0.75rem;
}

/* Toggle */
.sw-toggle { position: relative; width: 48px; height: 26px; display: inline-block; }
.sw-toggle input { opacity: 0; width: 0; height: 0; }
.sw-toggle .track {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background: #cbd5e1;
    border-radius: 26px;
    transition: 0.3s;
}
.sw-toggle .track:before {
    content: "";
    position: absolute;
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 3px;
    background: white;
    border-radius: 50%;
    transition: 0.3s;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
.sw-toggle input:checked + .track { background: #0ea5e9; }
.sw-toggle input:checked + .track:before { transform: translateX(22px); }

/* Form */
.lbl { font-weight: 600; font-size: 0.8rem; color: #475569; margin-bottom: 0.35rem; }
.inp {
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    padding: 0.6rem 1rem;
    font-size: 0.88rem;
    transition: all 0.3s;
}
.inp:focus { border-color: #0ea5e9; box-shadow: 0 0 0 3px rgba(14,165,233,0.15); }

/* Btn */
.btn-rp {
    background: linear-gradient(135deg, #0ea5e9, #6366f1);
    color: white;
    border: none;
    padding: 0.6rem 1.5rem;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s;
}
.btn-rp:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(14,165,233,0.4); color: white; }

/* Txn list */
.rp-txn-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 1.25rem;
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.2s;
}
.rp-txn-item:hover { background: #f8fafc; }
.rp-txn-item:last-child { border-bottom: none; }

@media (max-width: 768px) {
    .rp-co-stats { grid-template-columns: repeat(2, 1fr); }
}
</style>
@endpush

@section('content')
<div class="container-fluid">

    <!-- Header -->
    <div class="rp-co-header">
        <div class="d-flex justify-content-between align-items-start position-relative" style="z-index: 1;">
            <div class="d-flex align-items-center gap-3">
                <div class="rp-co-logo"><i class="bi bi-shield-lock-fill"></i></div>
                <div>
                    <h4 class="fw-bold mb-1">Reava Pay Integration</h4>
                    <p class="mb-0 opacity-75">Accept M-Pesa, Card, and Bank payments from your tenants</p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if(!$platformActive && !$settings->hasValidCredentials())
                <span class="badge bg-danger px-3 py-2" style="border-radius: 50px;">Platform Not Active</span>
                @else
                <span class="badge {{ $settings->is_active ? 'bg-success' : 'bg-secondary' }} px-3 py-2" style="border-radius: 50px;">
                    {{ $settings->is_active ? 'Enabled' : 'Disabled' }}
                </span>
                @endif
            </div>
        </div>

        <div class="rp-co-stats position-relative" style="z-index: 1;">
            <div class="rp-co-stat">
                <div class="value">{{ number_format($stats['total_transactions']) }}</div>
                <div class="label">Transactions</div>
            </div>
            <div class="rp-co-stat">
                <div class="value">KES {{ number_format($stats['completed_amount'], 0) }}</div>
                <div class="label">Total Collected</div>
            </div>
            <div class="rp-co-stat">
                <div class="value">KES {{ number_format($stats['this_month'], 0) }}</div>
                <div class="label">This Month</div>
            </div>
            <div class="rp-co-stat">
                <div class="value">{{ $stats['pending_count'] }}</div>
                <div class="label">Pending</div>
            </div>
        </div>
    </div>

    <!-- Credentials Display (shown after connect or always visible) -->
    @if($credentials)
    <div class="rp-card mb-4">
        <div class="rp-card-head" style="background: linear-gradient(to right, #f0f9ff, #eff6ff);">
            <div class="ico" style="background: linear-gradient(135deg, #0ea5e9, #6366f1); color: white;">
                <i class="bi bi-shield-check"></i>
            </div>
            <div class="flex-grow-1">
                <h6 style="color: #0ea5e9;">Reava Pay Credentials</h6>
                <small class="text-muted">Your merchant credentials on Reava Pay. Use these to log in to the Reava Pay dashboard.</small>
            </div>
            @if($credentials['is_active'])
            <span class="badge bg-success rounded-pill px-3 py-2"><i class="bi bi-check-circle me-1"></i> Connected</span>
            @endif
        </div>
        <div class="rp-card-body" style="overflow: hidden;">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="lbl">Merchant ID</label>
                    <div class="cred-field">
                        <code class="cred-value">{{ $credentials['merchant_id'] ?? 'N/A' }}</code>
                        <button type="button" class="btn btn-sm btn-outline-primary border-0 flex-shrink-0" onclick="copyText('{{ $credentials['merchant_id'] ?? '' }}')"><i class="bi bi-clipboard"></i></button>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <label class="lbl">Login Email</label>
                    <div class="cred-field">
                        <code class="cred-value">{{ $credentials['login_email'] ?? $company->email }}</code>
                        <button type="button" class="btn btn-sm btn-outline-primary border-0 flex-shrink-0" onclick="copyText('{{ $credentials['login_email'] ?? $company->email }}')"><i class="bi bi-clipboard"></i></button>
                    </div>
                </div>
                @if($credentials['login_password'] ?? null)
                <div class="col-12 col-md-6">
                    <label class="lbl"><i class="bi bi-key me-1"></i>Login Password</label>
                    <div class="cred-field">
                        <code class="cred-value" id="loginPasswordDisplay">{{ str_repeat('•', strlen($credentials['login_password'])) }}</code>
                        <div class="d-flex gap-1 flex-shrink-0">
                            <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick="toggleLoginPassword()" id="togglePasswordBtn" title="Show/Hide"><i class="bi bi-eye"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick="copyText('{{ $credentials['login_password'] }}')" title="Copy"><i class="bi bi-clipboard"></i></button>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-1"><i class="bi bi-info-circle me-1"></i>Sign in at <a href="https://reavapay.com/login" target="_blank">reavapay.com</a></small>
                </div>
                @endif
                <div class="col-12 col-md-6">
                    <label class="lbl">Float Account</label>
                    <div class="cred-field">
                        <code class="cred-value">{{ $credentials['float_account'] ?? 'Pending' }}</code>
                        <button type="button" class="btn btn-sm btn-outline-primary border-0 flex-shrink-0" onclick="copyText('{{ $credentials['float_account'] ?? '' }}')"><i class="bi bi-clipboard"></i></button>
                    </div>
                </div>
                <div class="col-12">
                    <label class="lbl">API Key</label>
                    <div class="cred-field">
                        <code class="cred-value">{{ $credentials['api_key'] ?? 'Not set' }}</code>
                        <button type="button" class="btn btn-sm btn-outline-primary border-0 flex-shrink-0" onclick="copyText('{{ $credentials['api_key'] ?? '' }}')"><i class="bi bi-clipboard"></i></button>
                    </div>
                </div>
                @if($credentials['api_secret'])
                <div class="col-12">
                    <label class="lbl"><i class="bi bi-lock me-1"></i>API Secret</label>
                    <div class="cred-field">
                        <code class="cred-value" id="apiSecretDisplay">{{ str_repeat('•', 40) }}</code>
                        <div class="d-flex gap-1 flex-shrink-0">
                            <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick="toggleSecret()" id="toggleSecretBtn" title="Show/Hide"><i class="bi bi-eye"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick="copyText('{{ $credentials['api_secret'] }}')" title="Copy"><i class="bi bi-clipboard"></i></button>
                        </div>
                    </div>
                </div>
                @endif
                <div class="col-12 col-md-6">
                    <label class="lbl">Environment</label>
                    <div>
                        <span class="badge {{ $credentials['environment'] === 'production' ? 'bg-success' : 'bg-warning' }} rounded-pill px-3">
                            {{ ucfirst($credentials['environment'] ?? 'sandbox') }}
                        </span>
                    </div>
                </div>
                @if($credentials['connected_at'])
                <div class="col-12 col-md-6">
                    <label class="lbl">Connected Since</label>
                    <span class="text-muted small">{{ \Carbon\Carbon::parse($credentials['connected_at'])->format('M d, Y H:i') }}</span>
                </div>
                @endif
            </div>

            <hr class="my-3">
            <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2">
                <div class="d-flex align-items-start gap-2 small text-muted">
                    <i class="bi bi-info-circle flex-shrink-0 mt-1"></i>
                    <span>Your Gwinto wallet is synced with your Reava Pay float account. All transactions flow bi-directionally in real-time.</span>
                </div>
                <form action="{{ route('company.reava-pay.connect.process') }}" method="POST" class="d-inline flex-shrink-0">
                    @csrf
                    <input type="hidden" name="reconnect" value="1">
                    <button type="submit" class="btn btn-sm btn-outline-primary" style="border-radius: 8px;">
                        <i class="bi bi-arrow-clockwise me-1"></i> Reconnect
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        <!-- Main Settings -->
        <div class="col-lg-8">
            <form action="{{ route('company.reava-pay.update') }}" method="POST">
                @csrf

                <!-- Credentials -->
                <div class="rp-card">
                    <div class="rp-card-head">
                        <div class="ico" style="background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #2563eb;">
                            <i class="bi bi-key-fill"></i>
                        </div>
                        <div>
                            <h6>API Credentials</h6>
                            <small class="text-muted">Your Reava Pay merchant credentials. Leave blank to use platform credentials.</small>
                        </div>
                    </div>
                    <div class="rp-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="lbl">API Key</label>
                                <input type="text" class="form-control inp" name="api_key" value="{{ $settings->api_key }}" placeholder="pk_live_...">
                            </div>
                            <div class="col-md-6">
                                <label class="lbl">Public Key</label>
                                <input type="text" class="form-control inp" name="public_key" value="{{ $settings->public_key }}" placeholder="pk_pub_...">
                            </div>
                            <div class="col-md-6">
                                <label class="lbl">API Secret</label>
                                <input type="password" class="form-control inp" name="api_secret" placeholder="{{ $settings->api_secret_encrypted ? '••••••••••••' : 'sk_live_...' }}">
                                <small class="text-muted">Leave blank to keep existing</small>
                            </div>
                            <div class="col-md-6">
                                <label class="lbl">Webhook Secret</label>
                                <input type="password" class="form-control inp" name="webhook_secret" value="{{ $settings->webhook_secret }}" placeholder="whsec_...">
                            </div>
                        </div>

                        @if($platformActive)
                        <div class="mt-3 p-3 rounded-3" style="background: #f0f9ff; border: 1px solid #bae6fd;">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-info-circle-fill text-info"></i>
                                <small class="text-info fw-semibold">Platform credentials are available. Your own credentials are optional and will take priority if provided.</small>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Payment Channels -->
                <div class="rp-card">
                    <div class="rp-card-head">
                        <div class="ico" style="background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #059669;">
                            <i class="bi bi-grid-3x3-gap-fill"></i>
                        </div>
                        <h6>Payment Channels</h6>
                    </div>
                    <div class="rp-card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="channel-opt {{ $settings->mpesa_enabled ? 'on' : '' }}" onclick="this.querySelector('input').click(); this.classList.toggle('on')">
                                    <div class="ch-ico" style="background: #d1fae5; color: #059669;"><i class="bi bi-phone-fill"></i></div>
                                    <h6 class="fw-bold mb-0">M-Pesa</h6>
                                    <small class="text-muted">Mobile Money</small>
                                    <div class="mt-2">
                                        <label class="sw-toggle">
                                            <input type="checkbox" name="mpesa_enabled" value="1" {{ $settings->mpesa_enabled ? 'checked' : '' }} onclick="event.stopPropagation()">
                                            <span class="track"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="channel-opt {{ $settings->card_enabled ? 'on' : '' }}" onclick="this.querySelector('input').click(); this.classList.toggle('on')">
                                    <div class="ch-ico" style="background: #dbeafe; color: #2563eb;"><i class="bi bi-credit-card-fill"></i></div>
                                    <h6 class="fw-bold mb-0">Card</h6>
                                    <small class="text-muted">Visa / Mastercard</small>
                                    <div class="mt-2">
                                        <label class="sw-toggle">
                                            <input type="checkbox" name="card_enabled" value="1" {{ $settings->card_enabled ? 'checked' : '' }} onclick="event.stopPropagation()">
                                            <span class="track"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="channel-opt {{ $settings->bank_transfer_enabled ? 'on' : '' }}" onclick="this.querySelector('input').click(); this.classList.toggle('on')">
                                    <div class="ch-ico" style="background: #fef3c7; color: #d97706;"><i class="bi bi-bank2"></i></div>
                                    <h6 class="fw-bold mb-0">Bank Transfer</h6>
                                    <small class="text-muted">Direct Bank</small>
                                    <div class="mt-2">
                                        <label class="sw-toggle">
                                            <input type="checkbox" name="bank_transfer_enabled" value="1" {{ $settings->bank_transfer_enabled ? 'checked' : '' }} onclick="event.stopPropagation()">
                                            <span class="track"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Wallet & Settlement -->
                <div class="rp-card">
                    <div class="rp-card-head">
                        <div class="ico" style="background: linear-gradient(135deg, #fef3c7, #fde68a); color: #d97706;">
                            <i class="bi bi-wallet2"></i>
                        </div>
                        <h6>Wallet & Settlement</h6>
                    </div>
                    <div class="rp-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div>
                                        <label class="lbl mb-0">Auto-Credit Wallet</label>
                                        <br><small class="text-muted">Automatically credit wallet on payment</small>
                                    </div>
                                    <label class="sw-toggle">
                                        <input type="checkbox" name="auto_credit_wallet" value="1" {{ $settings->auto_credit_wallet ? 'checked' : '' }}>
                                        <span class="track"></span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div>
                                        <label class="lbl mb-0">Auto-Settle</label>
                                        <br><small class="text-muted">Automatically settle to bank account</small>
                                    </div>
                                    <label class="sw-toggle">
                                        <input type="checkbox" name="auto_settle" value="1" {{ $settings->auto_settle ? 'checked' : '' }}>
                                        <span class="track"></span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="lbl">Settlement Schedule</label>
                                <select class="form-select inp" name="settlement_schedule">
                                    <option value="">Manual</option>
                                    <option value="daily" {{ $settings->settlement_schedule === 'daily' ? 'selected' : '' }}>Daily</option>
                                    <option value="weekly" {{ $settings->settlement_schedule === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                    <option value="monthly" {{ $settings->settlement_schedule === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="lbl">Min Settlement Amount (KES)</label>
                                <input type="number" class="form-control inp" name="min_settlement_amount" value="{{ $settings->min_settlement_amount }}" step="0.01">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Save -->
                <div class="d-flex justify-content-between align-items-center">
                    <form action="{{ route('company.reava-pay.toggle') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn {{ $company->reava_pay_enabled ? 'btn-outline-danger' : 'btn-rp' }}">
                            <i class="bi bi-{{ $company->reava_pay_enabled ? 'pause-circle' : 'play-circle' }} me-1"></i>
                            {{ $company->reava_pay_enabled ? 'Disable Reava Pay' : 'Enable Reava Pay' }}
                        </button>
                    </form>
                    <button type="submit" class="btn btn-rp btn-lg px-5">
                        <i class="bi bi-check2-circle me-2"></i> Save Settings
                    </button>
                </div>
            </form>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Actions -->
            <div class="rp-card">
                <div class="rp-card-head">
                    <div class="ico" style="background: linear-gradient(135deg, #ede9fe, #ddd6fe); color: #7c3aed;">
                        <i class="bi bi-lightning-fill"></i>
                    </div>
                    <h6>Quick Actions</h6>
                </div>
                <div class="rp-card-body">
                    <div class="d-grid gap-2">
                        <form action="{{ route('company.reava-pay.test-connection') }}" method="POST" class="d-grid">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary" style="border-radius: 10px;">
                                <i class="bi bi-wifi me-1"></i> Test Connection
                            </button>
                        </form>
                        <a href="{{ route('company.reava-pay.transactions') }}" class="btn btn-outline-secondary" style="border-radius: 10px;">
                            <i class="bi bi-list-ul me-1"></i> View All Transactions
                        </a>
                    </div>

                    <hr class="my-3">
                    <div class="small">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Credentials</span>
                            <span class="badge bg-{{ $settings->hasValidCredentials() ? 'success' : ($platformActive ? 'info' : 'warning') }}">
                                {{ $settings->hasValidCredentials() ? 'Own' : ($platformActive ? 'Platform' : 'Missing') }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Verified</span>
                            <span class="badge bg-{{ $settings->is_verified ? 'success' : 'warning' }}">
                                {{ $settings->is_verified ? 'Yes' : 'No' }}
                            </span>
                        </div>
                        @if($settings->verified_at)
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Last Verified</span>
                            <span>{{ $settings->verified_at->diffForHumans() }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Webhook -->
            <div class="rp-card">
                <div class="rp-card-head">
                    <div class="ico" style="background: linear-gradient(135deg, #fce7f3, #fbcfe8); color: #db2777;">
                        <i class="bi bi-link-45deg"></i>
                    </div>
                    <h6>Webhook URL</h6>
                </div>
                <div class="rp-card-body">
                    <p class="text-muted small mb-2">Set this URL in your Reava Pay dashboard:</p>
                    <div class="bg-light rounded-3 p-2" style="word-break: break-all;">
                        <code class="small text-primary">{{ url('webhooks/reava-pay') }}</code>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="rp-card">
                <div class="rp-card-head">
                    <div class="ico" style="background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #2563eb;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <h6>Recent Transactions</h6>
                </div>
                <div class="rp-card-body p-0">
                    @forelse($recentTransactions as $txn)
                    <div class="rp-txn-item">
                        <div style="width: 32px; height: 32px; border-radius: 8px; background: {{ $txn->status === 'completed' ? '#ecfdf5' : ($txn->status === 'failed' ? '#fef2f2' : '#fefce8') }}; display: flex; align-items: center; justify-content: center; margin-right: 0.65rem; flex-shrink: 0;">
                            <i class="bi bi-{{ $txn->channel === 'mpesa' ? 'phone' : ($txn->channel === 'card' ? 'credit-card' : 'bank') }}"
                               style="font-size: 0.8rem; color: {{ $txn->status === 'completed' ? '#059669' : ($txn->status === 'failed' ? '#dc2626' : '#d97706') }};"></i>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-semibold text-truncate" style="font-size: 0.8rem;">{{ $txn->gwinto_reference }}</div>
                            <small class="text-muted">{{ $txn->created_at->diffForHumans() }}</small>
                        </div>
                        <div class="text-end ms-2">
                            <div class="fw-bold" style="font-size: 0.8rem;">{{ $txn->formatted_amount }}</div>
                            <span class="badge bg-{{ $txn->status_badge }}" style="font-size: 0.65rem;">{{ ucfirst($txn->status) }}</span>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4">
                        <i class="bi bi-inbox text-muted"></i>
                        <p class="text-muted small mb-0">No transactions yet</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function copyText(text) {
    navigator.clipboard.writeText(text).then(() => {
        const btn = event.currentTarget;
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check2 text-success"></i>';
        setTimeout(() => btn.innerHTML = original, 2000);
    });
}

let secretVisible = false;
const secretValue = @json($credentials['api_secret'] ?? '');
function toggleSecret() {
    const display = document.getElementById('apiSecretDisplay');
    const btn = document.getElementById('toggleSecretBtn');
    secretVisible = !secretVisible;
    if (secretVisible) {
        display.textContent = secretValue;
        btn.innerHTML = '<i class="bi bi-eye-slash"></i>';
    } else {
        display.textContent = '••••••••••••••••••••••••••••••••••••••••';
        btn.innerHTML = '<i class="bi bi-eye"></i>';
    }
}

let passwordVisible = false;
const passwordValue = @json($credentials['login_password'] ?? '');
function toggleLoginPassword() {
    const display = document.getElementById('loginPasswordDisplay');
    const btn = document.getElementById('togglePasswordBtn');
    if (!display) return;
    passwordVisible = !passwordVisible;
    if (passwordVisible) {
        display.textContent = passwordValue;
        btn.innerHTML = '<i class="bi bi-eye-slash"></i>';
    } else {
        display.textContent = '•'.repeat(passwordValue.length || 12);
        btn.innerHTML = '<i class="bi bi-eye"></i>';
    }
}
</script>
@endpush
