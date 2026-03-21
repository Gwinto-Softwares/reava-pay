@extends('layouts.tenant')

@section('title', 'Wallet Top-Up - Gwinto')
@section('page-title', 'Top-Up Wallet via Reava Pay')

@push('styles')
<style>
.topup-header {
    background: linear-gradient(135deg, #059669 0%, #10b981 50%, #0ea5e9 100%);
    border-radius: 20px;
    padding: 2rem;
    color: white;
    position: relative;
    overflow: hidden;
    margin-bottom: 2rem;
}
.topup-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -15%;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    border-radius: 50%;
}
.topup-logo { width: 50px; height: 50px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; }
.balance-display {
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 14px;
    padding: 1rem 1.5rem;
    text-align: center;
}
.balance-value { font-size: 2rem; font-weight: 800; }
.balance-label { font-size: 0.75rem; opacity: 0.7; text-transform: uppercase; letter-spacing: 0.5px; }

.topup-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    margin-bottom: 1.5rem;
}
.topup-card-head {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: linear-gradient(to right, #fafbfc, white);
}
.topup-card-head .ico { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
.topup-card-head h6 { font-weight: 700; font-size: 0.95rem; color: #1e293b; margin: 0; }
.topup-card-body { padding: 1.5rem; }

/* Quick amounts */
.quick-amounts { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; margin-bottom: 1.5rem; }
.quick-amount {
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 0.75rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    font-weight: 700;
    color: #475569;
}
.quick-amount:hover { border-color: #10b981; color: #059669; background: #ecfdf5; }
.quick-amount.selected { border-color: #10b981; background: #ecfdf5; color: #059669; }

/* Channel select */
.ch-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 1rem; margin: 1.5rem 0; }
.ch-item {
    border: 2px solid #e2e8f0;
    border-radius: 14px;
    padding: 1.25rem 1rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}
.ch-item:hover { border-color: #10b981; }
.ch-item.active { border-color: #10b981; background: #ecfdf5; }
.ch-item .ch-ico { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; margin: 0 auto 0.5rem; }

.inp { border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 0.75rem 1rem; font-size: 0.9rem; transition: all 0.3s; width: 100%; }
.inp:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.15); outline: none; }
.lbl { font-weight: 600; font-size: 0.8rem; color: #475569; margin-bottom: 0.4rem; display: block; }

.btn-topup {
    background: linear-gradient(135deg, #059669, #10b981);
    color: white;
    border: none;
    padding: 0.85rem 2rem;
    border-radius: 14px;
    font-weight: 700;
    font-size: 1rem;
    transition: all 0.3s;
    width: 100%;
}
.btn-topup:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(16,185,129,0.4); color: white; }

/* Recent */
.recent-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f1f5f9;
}
.recent-item:last-child { border-bottom: none; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 12px;">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Header -->
    <div class="topup-header">
        <div class="d-flex justify-content-between align-items-center position-relative" style="z-index: 1;">
            <div class="d-flex align-items-center gap-3">
                <div class="topup-logo"><i class="bi bi-wallet-fill"></i></div>
                <div>
                    <h4 class="fw-bold mb-1">Top-Up Wallet</h4>
                    <p class="mb-0 opacity-75">Add funds via M-Pesa, Card, or Bank Transfer</p>
                </div>
            </div>
            <div class="balance-display">
                <div class="balance-label">Current Balance</div>
                <div class="balance-value">KES {{ number_format($wallet->balance ?? 0, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <form action="{{ route('tenant.reava-pay.wallet-topup.process') }}" method="POST" id="topupForm">
                @csrf
                <input type="hidden" name="channel" id="selectedChannel" value="mpesa">

                <!-- Amount -->
                <div class="topup-card">
                    <div class="topup-card-head">
                        <div class="ico" style="background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #059669;"><i class="bi bi-cash-stack"></i></div>
                        <h6>Top-Up Amount</h6>
                    </div>
                    <div class="topup-card-body">
                        <div class="quick-amounts">
                            <div class="quick-amount" onclick="setAmount(500, this)">KES 500</div>
                            <div class="quick-amount" onclick="setAmount(1000, this)">KES 1,000</div>
                            <div class="quick-amount" onclick="setAmount(2500, this)">KES 2,500</div>
                            <div class="quick-amount" onclick="setAmount(5000, this)">KES 5,000</div>
                            <div class="quick-amount" onclick="setAmount(10000, this)">KES 10,000</div>
                            <div class="quick-amount" onclick="setAmount(20000, this)">KES 20,000</div>
                            <div class="quick-amount" onclick="setAmount(50000, this)">KES 50,000</div>
                            <div class="quick-amount" onclick="setAmount(100000, this)">KES 100,000</div>
                        </div>
                        <lbl class="lbl">Or enter custom amount</lbl>
                        <input type="number" class="inp" name="amount" id="amountInput" placeholder="Enter amount in KES" min="10" step="1" required>
                    </div>
                </div>

                <!-- Channel -->
                <div class="topup-card">
                    <div class="topup-card-head">
                        <div class="ico" style="background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #2563eb;"><i class="bi bi-grid-3x3-gap-fill"></i></div>
                        <h6>Payment Method</h6>
                    </div>
                    <div class="topup-card-body">
                        <div class="ch-grid">
                            @if(in_array('mpesa', $channels))
                            <div class="ch-item active" onclick="selectCh('mpesa', this)">
                                <div class="ch-ico" style="background: #d1fae5; color: #059669;"><i class="bi bi-phone-fill"></i></div>
                                <h6 class="fw-bold mb-0" style="font-size: 0.85rem;">M-Pesa</h6>
                            </div>
                            @endif
                            @if(in_array('card', $channels))
                            <div class="ch-item" onclick="selectCh('card', this)">
                                <div class="ch-ico" style="background: #dbeafe; color: #2563eb;"><i class="bi bi-credit-card-fill"></i></div>
                                <h6 class="fw-bold mb-0" style="font-size: 0.85rem;">Card</h6>
                            </div>
                            @endif
                            @if(in_array('bank_transfer', $channels))
                            <div class="ch-item" onclick="selectCh('bank_transfer', this)">
                                <div class="ch-ico" style="background: #fef3c7; color: #d97706;"><i class="bi bi-bank2"></i></div>
                                <h6 class="fw-bold mb-0" style="font-size: 0.85rem;">Bank</h6>
                            </div>
                            @endif
                        </div>

                        <!-- M-Pesa -->
                        <div id="mpesa-fields">
                            <lbl class="lbl">M-Pesa Phone Number</lbl>
                            <div class="d-flex">
                                <span style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-right: none; border-radius: 12px 0 0 12px; padding: 0.75rem 1rem; font-weight: 600; color: #475569;">+254</span>
                                <input type="tel" class="inp" name="phone" placeholder="7XXXXXXXX" value="{{ ltrim($tenant->phone ?? '', '+254') }}" style="border-radius: 0 12px 12px 0;">
                            </div>
                        </div>

                        <div id="card-fields" style="display: none;">
                            <lbl class="lbl">Email</lbl>
                            <input type="email" class="inp" name="email" value="{{ $tenant->email }}">
                        </div>

                        <div id="bank-fields" style="display: none;">
                            <lbl class="lbl">Email for Instructions</lbl>
                            <input type="email" class="inp" name="email" value="{{ $tenant->email }}">
                        </div>

                        <button type="submit" class="btn-topup mt-4" id="topupBtn">
                            <i class="bi bi-plus-circle me-2"></i> Top Up Wallet
                        </button>

                        <div class="text-center mt-3">
                            <small class="text-muted"><i class="bi bi-shield-lock me-1"></i> Secured by Reava Pay</small>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-5">
            <!-- Recent Top-ups -->
            <div class="topup-card">
                <div class="topup-card-head">
                    <div class="ico" style="background: linear-gradient(135deg, #ede9fe, #ddd6fe); color: #7c3aed;"><i class="bi bi-clock-history"></i></div>
                    <h6>Recent Top-Ups</h6>
                </div>
                <div class="topup-card-body p-0">
                    @forelse($recentTopUps as $topup)
                    <div class="recent-item">
                        <div style="width: 36px; height: 36px; border-radius: 10px; background: {{ $topup->status === 'completed' ? '#ecfdf5' : ($topup->status === 'failed' ? '#fef2f2' : '#fefce8') }}; display: flex; align-items: center; justify-content: center; margin-right: 0.75rem;">
                            <i class="bi bi-{{ $topup->channel === 'mpesa' ? 'phone' : ($topup->channel === 'card' ? 'credit-card' : 'bank') }}"
                               style="font-size: 0.85rem; color: {{ $topup->status === 'completed' ? '#059669' : ($topup->status === 'failed' ? '#dc2626' : '#d97706') }};"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold" style="font-size: 0.85rem;">{{ $topup->formatted_amount }}</div>
                            <small class="text-muted">{{ $topup->created_at->diffForHumans() }}</small>
                        </div>
                        <span class="badge bg-{{ $topup->status_badge }}" style="font-size: 0.7rem;">{{ ucfirst($topup->status) }}</span>
                    </div>
                    @empty
                    <div class="text-center py-4">
                        <i class="bi bi-inbox text-muted"></i>
                        <p class="text-muted small mb-0 mt-1">No recent top-ups</p>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Info -->
            <div class="topup-card">
                <div class="topup-card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-lightbulb me-2 text-warning"></i>How It Works</h6>
                    <div class="d-flex gap-2 mb-2">
                        <div style="width: 28px; height: 28px; border-radius: 8px; background: #ecfdf5; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <span class="fw-bold text-success" style="font-size: 0.75rem;">1</span>
                        </div>
                        <small class="text-muted">Enter amount and select payment method</small>
                    </div>
                    <div class="d-flex gap-2 mb-2">
                        <div style="width: 28px; height: 28px; border-radius: 8px; background: #ecfdf5; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <span class="fw-bold text-success" style="font-size: 0.75rem;">2</span>
                        </div>
                        <small class="text-muted">Complete payment on your phone or card</small>
                    </div>
                    <div class="d-flex gap-2">
                        <div style="width: 28px; height: 28px; border-radius: 8px; background: #ecfdf5; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <span class="fw-bold text-success" style="font-size: 0.75rem;">3</span>
                        </div>
                        <small class="text-muted">Wallet is credited instantly. Synced with Reava Pay.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function setAmount(amount, el) {
    document.getElementById('amountInput').value = amount;
    document.querySelectorAll('.quick-amount').forEach(q => q.classList.remove('selected'));
    el.classList.add('selected');
}
function selectCh(channel, el) {
    document.querySelectorAll('.ch-item').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('selectedChannel').value = channel;
    document.getElementById('mpesa-fields').style.display = channel === 'mpesa' ? 'block' : 'none';
    document.getElementById('card-fields').style.display = channel === 'card' ? 'block' : 'none';
    document.getElementById('bank-fields').style.display = channel === 'bank_transfer' ? 'block' : 'none';
}
document.getElementById('topupForm').addEventListener('submit', function() {
    const btn = document.getElementById('topupBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Processing...';
});
</script>
@endpush
