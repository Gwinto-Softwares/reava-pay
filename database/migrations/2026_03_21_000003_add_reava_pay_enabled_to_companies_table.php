<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('reava_pay_enabled')->default(false)->after('transaction_fee_mode');
            $table->boolean('reava_pay_configured')->default(false)->after('reava_pay_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['reava_pay_enabled', 'reava_pay_configured']);
        });
    }
};
