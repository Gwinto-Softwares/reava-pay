<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gwinto_sync_log', function (Blueprint $table) {
            $table->id();
            $table->string('direction'); // to_reava, from_reava
            $table->string('entity_type'); // company, tenant, wallet_transaction
            $table->unsignedBigInteger('entity_id');
            $table->string('reava_reference')->nullable();
            $table->string('status'); // synced, failed, pending
            $table->json('payload')->nullable();
            $table->json('response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index('status');
            $table->index('direction');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gwinto_sync_log');
    }
};
