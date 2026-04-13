<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reava_pay_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('payer_type'); // App\Models\Tenant, App\Models\Company
            $table->unsignedBigInteger('payer_id');
            $table->string('payee_type')->nullable(); // App\Models\Company, system
            $table->unsignedBigInteger('payee_id')->nullable();
            $table->string('type'); // collection, payout, wallet_topup, invoice_payment, settlement
            $table->string('channel'); // mpesa, card, bank_transfer
            $table->decimal('amount', 14, 2);
            $table->decimal('charge_amount', 14, 2)->default(0);
            $table->decimal('net_amount', 14, 2);
            $table->string('currency', 10)->default('KES');
            $table->string('status')->default('pending'); // pending, processing, completed, failed, reversed, expired
            $table->string('reava_reference')->nullable(); // Reference from Reava Pay API
            $table->string('provider_reference')->nullable(); // M-Pesa/Card/Bank reference
            $table->string('gwinto_reference')->unique(); // Internal reference
            $table->string('idempotency_key')->nullable()->unique();
            $table->string('phone')->nullable(); // For M-Pesa
            $table->string('email')->nullable(); // For card
            $table->string('account_reference')->nullable();
            $table->text('description')->nullable();
            $table->string('authorization_url')->nullable(); // For card redirects
            $table->string('callback_url')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('wallet_transaction_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->json('reava_response')->nullable(); // Raw API response
            $table->json('webhook_payload')->nullable(); // Raw webhook data
            $table->json('metadata')->nullable();
            $table->string('failure_reason')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index(['payer_type', 'payer_id']);
            $table->index(['payee_type', 'payee_id']);
            $table->index('status');
            $table->index('type');
            $table->index('channel');
            $table->index('reava_reference');
            $table->index('invoice_id');
            $table->index('created_at');

            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reava_pay_transactions');
    }
};
