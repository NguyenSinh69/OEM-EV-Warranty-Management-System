<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('status_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warranty_claim_id')->constrained()->onDelete('cascade');
            $table->enum('from_status', [
                'SUBMITTED',
                'UNDER_REVIEW',
                'APPROVED',
                'REJECTED',
                'PROCESSING',
                'COMPLETED',
                'CANCELLED'
            ]);
            $table->enum('to_status', [
                'SUBMITTED',
                'UNDER_REVIEW',
                'APPROVED',
                'REJECTED',
                'PROCESSING',
                'COMPLETED',
                'CANCELLED'
            ]);
            $table->string('reason')->nullable();
            $table->string('changed_by');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['warranty_claim_id']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_changes');
    }
};