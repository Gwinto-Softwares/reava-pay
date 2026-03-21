@extends('layouts.tenant')

@section('title', 'Payment Status - Gwinto')
@section('page-title', 'Payment Status')

@push('styles')
<style>
.status-container {
    max-width: 560px;
    margin: 2rem auto;
}
.status-card {
    background: white;
    border-radius: 20px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
}
.status-header {
    padding: 2.5rem 2rem;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.status-header.pending { background: linear-gradient(135deg, #fef3c7, #fde68a); }
.status-header.processing { background: linear-gradient(135deg, #dbeafe, #93c5fd); }
.status-header.completed { background: linear-gradient(135deg, #d1fae5, #6ee7b7); }
.status-header.failed { background: linear-gradient(135deg, #fee2e2, #fca5a5); }

.status-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 2rem;
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
}
.status-icon.pending { color: #d97706; }
.status-icon.processing { color: #2563eb; }
.status-icon.completed { color: #059669; }
.status-icon.failed { color: #dc2626; }

@keyframes spin { 100% { transform: rotate(360deg); } }
.status-icon.processing i { animation: spin 2s linear infinite; }

.status-body { padding: 2rem; }
.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.9rem;
}
.detail-row:last-child { border-bottom: none; }

.btn-action {
    border-radius: 12px;
    padding: 0.7rem 1.5rem;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 12px; max-width: 560px; margin: 0 auto 1rem;">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="status-container">
        <div class="status-card" id="statusCard">
            <div class="status-header {{ $transaction->status }}" id="statusHeader">
                <div class="status-icon {{ $transaction->status }}" id="statusIcon">
                    @if($transaction->status === 'pending' || $transaction->status === 'processing')
                    <i class="bi bi-arrow-repeat"></i>
                    @elseif($transaction->status === 'completed')
                    <i class="bi bi-check-circle-fill"></i>
                    @elseif($transaction->status === 'failed')
                    <i class="bi bi-x-circle-fill"></i>
                    @endif
                </div>
                <h4 class="fw-bold mb-1" id="statusTitle">
                    @if($transaction->status === 'pending' || $transaction->status === 'processing')
                    Payment Processing
                    @elseif($transaction->status === 'completed')
                    Payment Successful
                    @elseif($transaction->status === 'failed')
                    Payment Failed
                    @endif
                </h4>
                <p class="mb-0 opacity-75" id="statusMessage">
                    @if($transaction->status === 'pending' || $transaction->status === 'processing')
                    Please complete the payment on your device
                    @elseif($transaction->status === 'completed')
                    Your payment has been received and processed
                    @elseif($transaction->status === 'failed')
                    {{ $transaction->failure_reason ?? 'The payment could not be completed' }}
                    @endif
                </p>
            </div>

            <div class="status-body">
                <div class="text-center mb-3">
                    <div style="font-size: 2rem; font-weight: 800; color: #1e293b;" id="amountDisplay">{{ $transaction->formatted_amount }}</div>
                </div>

                <div class="detail-row">
                    <span class="text-muted">Reference</span>
                    <span class="fw-semibold" id="refDisplay">{{ $transaction->gwinto_reference }}</span>
                </div>
                <div class="detail-row">
                    <span class="text-muted">Type</span>
                    <span class="fw-semibold">{{ $transaction->type_label }}</span>
                </div>
                <div class="detail-row">
                    <span class="text-muted">Channel</span>
                    <span class="fw-semibold">{{ $transaction->channel_label }}</span>
                </div>
                @if($transaction->phone)
                <div class="detail-row">
                    <span class="text-muted">Phone</span>
                    <span class="fw-semibold">{{ $transaction->phone }}</span>
                </div>
                @endif
                <div class="detail-row" id="providerRefRow" style="{{ $transaction->provider_reference ? '' : 'display:none' }}">
                    <span class="text-muted">Provider Reference</span>
                    <span class="fw-semibold" id="providerRefDisplay">{{ $transaction->provider_reference ?? '-' }}</span>
                </div>
                <div class="detail-row">
                    <span class="text-muted">Date</span>
                    <span class="fw-semibold">{{ $transaction->created_at->format('M d, Y H:i') }}</span>
                </div>
                <div class="detail-row" id="completedRow" style="{{ $transaction->completed_at ? '' : 'display:none' }}">
                    <span class="text-muted">Completed</span>
                    <span class="fw-semibold" id="completedDisplay">{{ $transaction->completed_at?->format('M d, Y H:i') ?? '-' }}</span>
                </div>

                <div class="d-flex gap-2 mt-4">
                    @if($transaction->invoice_id)
                    <a href="{{ route('tenant.invoices.index') }}" class="btn btn-outline-primary btn-action flex-fill">
                        <i class="bi bi-receipt me-1"></i> View Invoices
                    </a>
                    @endif
                    <a href="{{ route('tenant.dashboard') }}" class="btn btn-primary btn-action flex-fill" style="background: linear-gradient(135deg, #0ea5e9, #6366f1); border: none;">
                        <i class="bi bi-house me-1"></i> Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
@if(in_array($transaction->status, ['pending', 'processing']))
// Poll for status updates every 5 seconds
let pollInterval = setInterval(function() {
    fetch('{{ route("reava-pay.payment.check-status", $transaction->gwinto_reference) }}')
        .then(r => r.json())
        .then(data => {
            if (data.status === 'completed') {
                clearInterval(pollInterval);
                updateUI('completed', data);
            } else if (data.status === 'failed') {
                clearInterval(pollInterval);
                updateUI('failed', data);
            }
        })
        .catch(() => {});
}, 5000);

// Stop polling after 5 minutes
setTimeout(() => clearInterval(pollInterval), 300000);

function updateUI(status, data) {
    const header = document.getElementById('statusHeader');
    const icon = document.getElementById('statusIcon');
    const title = document.getElementById('statusTitle');
    const message = document.getElementById('statusMessage');

    header.className = 'status-header ' + status;
    icon.className = 'status-icon ' + status;

    if (status === 'completed') {
        icon.innerHTML = '<i class="bi bi-check-circle-fill"></i>';
        title.textContent = 'Payment Successful';
        message.textContent = 'Your payment has been received and your wallet has been credited.';

        if (data.provider_reference) {
            document.getElementById('providerRefRow').style.display = '';
            document.getElementById('providerRefDisplay').textContent = data.provider_reference;
        }
        if (data.completed_at) {
            document.getElementById('completedRow').style.display = '';
            document.getElementById('completedDisplay').textContent = data.completed_at;
        }
    } else if (status === 'failed') {
        icon.innerHTML = '<i class="bi bi-x-circle-fill"></i>';
        title.textContent = 'Payment Failed';
        message.textContent = data.failure_reason || 'The payment could not be completed.';
    }
}
@endif
</script>
@endpush
