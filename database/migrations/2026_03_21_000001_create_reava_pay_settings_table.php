<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reava_pay_settings', function (Blueprint $table) {
            $table->id();
            $table->string('scope_type')->default('platform'); // platform, company
            $table->unsignedBigInteger('scope_id')->nullable(); // null for platform, company_id for company
            $table->string('api_key')->nullable();
            $table->string('public_key')->nullable();
            $table->text('api_secret_encrypted')->nullable();
            $table->string('webhook_secret')->nullable();
            $table->string('base_url')->default('https://reavapay.com/api/v1');
            $table->string('environment')->default('sandbox'); // sandbox, production
            $table->string('default_currency')->default('KES');
            $table->boolean('mpesa_enabled')->default(true);
            $table->boolean('card_enabled')->default(true);
            $table->boolean('bank_transfer_enabled')->default(true);
            $table->boolean('auto_credit_wallet')->default(true);
            $table->boolean('auto_settle')->default(false);
            $table->string('settlement_schedule')->nullable(); // daily, weekly, monthly
            $table->decimal('min_settlement_amount', 14, 2)->default(1000);
            $table->decimal('min_transaction_amount', 14, 2)->default(10);
            $table->decimal('max_transaction_amount', 14, 2)->default(500000);
            $table->string('webhook_url')->nullable();
            $table->string('callback_url')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['scope_type', 'scope_id']);
            $table->index('scope_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reava_pay_settings');
    }
};
