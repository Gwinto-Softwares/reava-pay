@extends('layouts.company')

@section('title', 'Connect Reava Pay - ' . $company->name)
@section('page-title', 'Connect Reava Pay')

@push('styles')
<style>
.connect-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 40%, #0ea5e9 100%);
    border-radius: 24px;
    padding: 3rem;
    color: white;
    position: relative;
    overflow: hidden;
    text-align: center;
    margin-bottom: 2rem;
}
.connect-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(14, 165, 233, 0.25) 0%, transparent 70%);
    border-radius: 50%;
}
.connect-hero::after {
    content: '';
    position: absolute;
    bottom: -40%;
    left: -10%;
    width: 350px;
    height: 350px;
    background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, transparent 70%);
    border-radius: 50%;
}
.connect-logo {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #0ea5e9, #6366f1);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.25rem;
    margin: 0 auto 1.5rem;
    box-shadow: 0 12px 40px rgba(14, 165, 233, 0.5);
}
.features-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    margin: 2rem 0;
}
.feature-card {
    background: white;
    border-radius: 16px;
    padding: 2rem 1.5rem;
    text-align: center;
    border: 1px solid #e2e8f0;
    transition: all 0.3s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.feature-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.1);
}
.feature-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin: 0 auto 1rem;
}
.btn-connect {
    background: linear-gradient(135deg, #0ea5e9, #6366f1);
    color: white;
    border: none;
    padding: 1rem 3rem;
    border-radius: 14px;
    font-weight: 700;
    font-size: 1.1rem;
    transition: all 0.3s;
    box-shadow: 0 8px 24px rgba(14, 165, 233, 0.4);
}
.btn-connect:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 32px rgba(14, 165, 233, 0.5);
    color: white;
}
.steps-section {
    background: white;
    border-radius: 20px;
    padding: 2.5rem;
    border: 1px solid #e2e8f0;
    margin-bottom: 2rem;
}
.step-item {
    display: flex;
    align-items: flex-start;
    gap: 1.25rem;
    margin-bottom: 1.75rem;
}
.step-item:last-child { margin-bottom: 0; }
.step-number {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: linear-gradient(135deg, #0ea5e9, #6366f1);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 1rem;
    flex-shrink: 0;
}
@media (max-width: 768px) {
    .features-grid { grid-template-columns: 1fr; }
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

    <!-- Hero -->
    <div class="connect-hero">
        <div class="position-relative" style="z-index: 1;">
            <div class="connect-logo">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <h2 class="fw-bold mb-2">Connect to Reava Pay</h2>
            <p class="mb-4 opacity-80" style="max-width: 500px; margin: 0 auto;">
                Enable secure payment collection from your tenants via M-Pesa, Cards, and Bank Transfers powered by Reava Pay
            </p>
            <form action="{{ route('company.reava-pay.connect.process') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn-connect">
                    <i class="bi bi-plug me-2"></i> Connect Now
                </button>
            </form>
            <p class="mt-3 mb-0 opacity-60 small">
                <i class="bi bi-lock me-1"></i> Your credentials are encrypted and stored securely
            </p>
        </div>
    </div>

    <!-- Features -->
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon" style="background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #059669;">
                <i class="bi bi-phone-fill"></i>
            </div>
            <h5 class="fw-bold mb-2">M-Pesa Payments</h5>
            <p class="text-muted small mb-0">Accept rent payments directly via M-Pesa STK Push. Tenants pay from their phone with one tap.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon" style="background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #2563eb;">
                <i class="bi bi-credit-card-fill"></i>
            </div>
            <h5 class="fw-bold mb-2">Card Payments</h5>
            <p class="text-muted small mb-0">Accept Visa and Mastercard payments through a secure, PCI-compliant checkout experience.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon" style="background: linear-gradient(135deg, #fef3c7, #fde68a); color: #d97706;">
                <i class="bi bi-wallet2"></i>
            </div>
            <h5 class="fw-bold mb-2">Instant Wallet Credit</h5>
            <p class="text-muted small mb-0">Payments are instantly reflected in your Gwinto wallet. Your wallet mirrors your Reava Pay float account.</p>
        </div>
    </div>

    <!-- How it Works -->
    <div class="steps-section">
        <h4 class="fw-bold mb-4"><i class="bi bi-lightning-charge me-2 text-primary"></i>How It Works</h4>
        <div class="step-item">
            <div class="step-number">1</div>
            <div>
                <h6 class="fw-bold mb-1">Instant Registration</h6>
                <p class="text-muted mb-0">Click "Connect Now" and your company is automatically registered on Reava Pay with a merchant account and float account created for you.</p>
            </div>
        </div>
        <div class="step-item">
            <div class="step-number">2</div>
            <div>
                <h6 class="fw-bold mb-1">Credentials Generated</h6>
                <p class="text-muted mb-0">Your API keys and login credentials are securely generated. You can also log in to the Reava Pay dashboard directly.</p>
            </div>
        </div>
        <div class="step-item">
            <div class="step-number">3</div>
            <div>
                <h6 class="fw-bold mb-1">Start Collecting Payments</h6>
                <p class="text-muted mb-0">Your tenants can immediately pay invoices and top up their wallets via M-Pesa, Card, or Bank Transfer.</p>
            </div>
        </div>
        <div class="step-item">
            <div class="step-number">4</div>
            <div>
                <h6 class="fw-bold mb-1">Real-Time Sync</h6>
                <p class="text-muted mb-0">Your Gwinto wallet balance is synced with your Reava Pay float account in real-time. Every transaction is tracked.</p>
            </div>
        </div>
    </div>
</div>
@endsection
