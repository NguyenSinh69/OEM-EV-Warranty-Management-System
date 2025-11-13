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
        Schema::create('warranty_claims', function (Blueprint $table) {
            $table->id();
            $table->string('claim_number')->unique();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            
            // Claim details
            $table->enum('claim_type', [
                'MANUFACTURING_DEFECT',
                'NORMAL_WEAR',
                'ACCIDENTAL_DAMAGE',
                'ELECTRICAL_ISSUE',
                'BATTERY_ISSUE',
                'SOFTWARE_ISSUE'
            ]);
            $table->string('title');
            $table->text('description');
            $table->date('issue_date');
            $table->integer('reported_mileage');
            
            // Status and workflow
            $table->enum('status', [
                'SUBMITTED',
                'UNDER_REVIEW',
                'APPROVED',
                'REJECTED',
                'PROCESSING',
                'COMPLETED',
                'CANCELLED'
            ])->default('SUBMITTED');
            $table->enum('priority', [
                'LOW',
                'MEDIUM',
                'HIGH',
                'CRITICAL'
            ])->default('MEDIUM');
            $table->string('assigned_to')->nullable();
            
            // Resolution
            $table->json('resolution')->nullable(); // {decision, reason, estimated_cost, approved_by, approved_at, repair_instructions}
            
            // Timestamps
            $table->timestamp('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['claim_number']);
            $table->index(['status']);
            $table->index(['priority']);
            $table->index(['customer_id']);
            $table->index(['product_id']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warranty_claims');
    }
};