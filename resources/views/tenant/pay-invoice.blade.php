@extends('layouts.tenant')

@section('title', 'Pay Invoice - Gwinto')
@section('page-title', 'Pay Invoice via Reava Pay')

@push('styles')
<style>
.pay-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0ea5e9 100%);
    border-radius: 20px;
    padding: 2rem;
    color: white;
    position: relative;
    overflow: hidden;
    margin-bottom: 2rem;
}
.pay-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -15%;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(14, 165, 233, 0.2) 0%, transparent 70%);
    border-radius: 50%;
}
.pay-logo { width: 50px; height: 50px; background: linear-gradient(135deg, #0ea5e9, #6366f1); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; box-shadow: 0 8px 24px rgba(14, 165, 233, 0.4); }

/* Invoice Summary */
.invoice-summary {
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}
.invoice-detail { display: flex; justify-content: space-between; padding: 0.6rem 0; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
.invoice-detail:last-child { border-bottom: none; }
.invoice-total { background: linear-gradient(135deg, #f0f9ff, #eff6ff); border-radius: 12px; padding: 1rem 1.25rem; margin-top: 0.75rem; }

/* Channel Select */
.channel-select { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin: 1.5rem 0; }
.channel-option {
    border: 2px solid #e2e8f0;
    border-radius: 16px;
    padding: 1.5rem 1rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    position: relative;
}
.channel-option:hover { border-color: #0ea5e9; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
.channel-option.selected { border-color: #0ea5e9; background: #f0f9ff; box-shadow: 0 0 0 3px rgba(14,165,233,0.15); }
.channel-option .check-mark {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #0ea5e9;
    color: white;
    display: none;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
}
.channel-option.selected .check-mark { display: flex; }
.channel-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    margin: 0 auto 0.75rem;
}

/* Form Card */
.pay-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    margin-bottom: 1.5rem;
}
.pay-card-head {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: linear-gradient(to right, #fafbfc, white);
}
.pay-card-head .ico { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
.pay-card-head h6 { font-weight: 700; font-size: 0.95rem; color: #1e293b; margin: 0; }
.pay-card-body { padding: 1.5rem; }

/* Input */
.pay-input {
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 0.75rem 1rem;
    font-size: 0.9rem;
    transition: all 0.3s;
    width: 100%;
}
.pay-input:focus { border-color: #0ea5e9; box-shadow: 0 0 0 3px rgba(14,165,233,0.15); outline: none; }
.pay-label { font-weight: 600; font-size: 0.8rem; color: #475569; margin-bottom: 0.4rem; display: block; }

/* Button */
.btn-pay {
    background: linear-gradient(135deg, #0ea5e9, #6366f1);
    color: white;
    border: none;
    padding: 0.85rem 2rem;
    border-radius: 14px;
    font-weight: 700;
    font-size: 1rem;
    transition: all 0.3s;
    width: 100%;
}
.btn-pay:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(14,165,233,0.4); color: white; }
.btn-pay:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

/* Phone input */
.phone-prefix {
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-right: none;
    border-radius: 12px 0 0 12px;
    padding: 0.75rem 1rem;
    font-weight: 600;
    color: #475569;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 12px; border: none;">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Header -->
    <div class="pay-header">
        <div class="d-flex align-items-center gap-3 position-relative" style="z-index: 1;">
            <div class="pay-logo"><i class="bi bi-shield-lock-fill"></i></div>
            <div>
                <h4 class="fw-bold mb-1">Pay Invoice</h4>
                <p class="mb-0 opacity-75">Secure payment via Reava Pay</p>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Payment Form -->
        <div class="col-lg-7">
            <form action="{{ route('tenant.reava-pay.pay-invoice.process', $invoice->id) }}" method="POST" id="paymentForm">
                @csrf
                <input type="hidden" name="channel" id="selectedChannel" value="mpesa">

                <!-- Select Payment Channel -->
                <div class="pay-card">
                    <div class="pay-card-head">
                        <div class="ico" style="background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #2563eb;"><i class="bi bi-grid-3x3-gap-fill"></i></div>
                        <h6>Select Payment Method</h6>
                    </div>
                    <div class="pay-card-body">
                        <div class="channel-select">
                            @if(in_array('mpesa', $channels))
                            <div class="channel-option selected" onclick="selectChannel('mpesa', this)">
                                <div class="check-mark"><i class="bi bi-check"></i></div>
                                <div class="channel-icon" style="background: #d1fae5; color: #059669;"><i class="bi bi-phone-fill"></i></div>
                                <h6 class="fw-bold mb-0" style="font-size: 0.9rem;">M-Pesa</h6>
                                <small class="text-muted">Mobile Money</small>
                            </div>
                            @endif
                            @if(in_array('card', $channels))
                            <div class="channel-option" onclick="selectChannel('card', this)">
                                <div class="check-mark"><i class="bi bi-check"></i></div>
                                <div class="channel-icon" style="background: #dbeafe; color: #2563eb;"><i class="bi bi-credit-card-fill"></i></div>
                                <h6 class="fw-bold mb-0" style="font-size: 0.9rem;">Card</h6>
                                <small class="text-muted">Visa / Mastercard</small>
                            </div>
                            @endif
                            @if(in_array('bank_transfer', $channels))
                            <div class="channel-option" onclick="selectChannel('bank_transfer', this)">
                                <div class="check-mark"><i class="bi bi-check"></i></div>
                                <div class="channel-icon" style="background: #fef3c7; color: #d97706;"><i class="bi bi-bank2"></i></div>
                                <h6 class="fw-bold mb-0" style="font-size: 0.9rem;">Bank Transfer</h6>
                                <small class="text-muted">Direct Bank</small>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Payment Details -->
                <div class="pay-card">
                    <div class="pay-card-head">
                        <div class="ico" style="background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #059669;"><i class="bi bi-pencil-square"></i></div>
                        <h6>Payment Details</h6>
                    </div>
                    <div class="pay-card-body">
                        <!-- M-Pesa Fields -->
                        <div id="mpesa-fields">
                            <div class="mb-3">
                                <label class="pay-label">M-Pesa Phone Number</label>
                                <div class="d-flex">
                                    <span class="phone-prefix">+254</span>
                                    <input type="tel" class="pay-input" name="phone" placeholder="7XXXXXXXX" value="{{ ltrim($tenant->phone ?? '', '+254') }}" style="border-radius: 0 12px 12px 0;">
                                </div>
                                <small class="text-muted mt-1 d-block">You will receive an STK push on this number</small>
                            </div>
                        </div>

                        <!-- Card Fields -->
                        <div id="card-fields" style="display: none;">
                            <div class="mb-3">
                                <label class="pay-label">Email Address</label>
                                <input type="email" class="pay-input" name="email" value="{{ $tenant->email }}" placeholder="your@email.com">
                                <small class="text-muted mt-1 d-block">You will be redirected to a secure payment page</small>
                            </div>
                        </div>

                        <!-- Bank Fields -->
                        <div id="bank-fields" style="display: none;">
                            <div class="p-3 rounded-3 mb-3" style="background: #fef3c7; border: 1px solid #fde68a;">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-info-circle-fill text-warning"></i>
                                    <small class="text-dark fw-semibold">You will receive bank transfer details to complete your payment.</small>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="pay-label">Email for Instructions</label>
                                <input type="email" class="pay-input" name="email" value="{{ $tenant->email }}" placeholder="your@email.com">
                            </div>
                        </div>

                        <!-- Amount -->
                        <div class="mb-3">
                            <label class="pay-label">Amount (KES)</label>
                            <input type="number" class="pay-input" name="amount" value="{{ $remainingAmount }}" min="1" max="{{ $remainingAmount }}" step="0.01">
                            <small class="text-muted mt-1 d-block">Outstanding balance: KES {{ number_format($remainingAmount, 2) }}</small>
                        </div>

                        <!-- Submit -->
                        <button type="submit" class="btn-pay mt-3" id="payBtn">
                            <i class="bi bi-lock-fill me-2"></i> Pay KES {{ number_format($remainingAmount, 2) }}
                        </button>

                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="bi bi-shield-lock me-1"></i> Secured by Reava Pay. Your payment data is encrypted.
                            </small>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Invoice Summary -->
        <div class="col-lg-5">
            <div class="invoice-summary">
                <h6 class="fw-bold mb-3"><i class="bi bi-receipt me-2 text-primary"></i>Invoice Summary</h6>
                <div class="invoice-detail">
                    <span class="text-muted">Invoice Number</span>
                    <span class="fw-semibold">{{ $invoice->invoice_number }}</span>
                </div>
                <div class="invoice-detail">
                    <span class="text-muted">Property</span>
                    <span class="fw-semibold">{{ $invoice->tenancyAgreement?->unit?->property?->name ?? 'N/A' }}</span>
                </div>
                <div class="invoice-detail">
                    <span class="text-muted">Unit</span>
                    <span class="fw-semibold">{{ $invoice->tenancyAgreement?->unit?->unit_number ?? 'N/A' }}</span>
                </div>
                <div class="invoice-detail">
                    <span class="text-muted">Due Date</span>
                    <span class="fw-semibold">{{ $invoice->due_date?->format('M d, Y') ?? 'N/A' }}</span>
                </div>
                <div class="invoice-detail">
                    <span class="text-muted">Total Amount</span>
                    <span class="fw-semibold">KES {{ number_format($invoice->amount, 2) }}</span>
                </div>
                <div class="invoice-detail">
                    <span class="text-muted">Already Paid</span>
                    <span class="fw-semibold text-success">KES {{ number_format($invoice->paid_amount, 2) }}</span>
                </div>
                <div class="invoice-total">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-primary">Amount Due</span>
                        <span class="fw-bold text-primary" style="font-size: 1.25rem;">KES {{ number_format($remainingAmount, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Company Info -->
            <div class="invoice-summary">
                <h6 class="fw-bold mb-3"><i class="bi bi-building me-2 text-primary"></i>Payment To</h6>
                <div class="d-flex align-items-center gap-3">
                    <div style="width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, #e0e7ff, #c7d2fe); display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-building text-primary" style="font-size: 1.25rem;"></i>
                    </div>
                    <div>
                        <div class="fw-bold">{{ $company->name }}</div>
                        <small class="text-muted">{{ $company->email }}</small>
                    </div>
                </div>
            </div>

            <!-- Wallet Balance -->
            <div class="invoice-summary" style="background: linear-gradient(135deg, #f0f9ff, #eff6ff); border-color: #bae6fd;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted">Your Wallet Balance</small>
                        <div class="fw-bold text-primary" style="font-size: 1.25rem;">KES {{ number_format($tenant->wallet?->balance ?? 0, 2) }}</div>
                    </div>
                    <div style="width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, #0ea5e9, #6366f1); display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-wallet2 text-white" style="font-size: 1.2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function selectChannel(channel, element) {
    document.querySelectorAll('.channel-option').forEach(el => el.classList.remove('selected'));
    element.classList.add('selected');
    document.getElementById('selectedChannel').value = channel;

    document.getElementById('mpesa-fields').style.display = channel === 'mpesa' ? 'block' : 'none';
    document.getElementById('card-fields').style.display = channel === 'card' ? 'block' : 'none';
    document.getElementById('bank-fields').style.display = channel === 'bank_transfer' ? 'block' : 'none';
}

document.getElementById('paymentForm').addEventListener('submit', function() {
    const btn = document.getElementById('payBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Processing...';
});
</script>
@endpush
