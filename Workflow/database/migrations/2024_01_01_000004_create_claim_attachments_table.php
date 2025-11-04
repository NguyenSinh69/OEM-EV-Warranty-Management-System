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
        Schema::create('claim_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warranty_claim_id')->constrained()->onDelete('cascade');
            $table->string('file_name');
            $table->string('file_type');
            $table->integer('file_size'); // in bytes
            $table->string('file_path');
            $table->string('description')->nullable();
            $table->string('uploaded_by');
            $table->timestamps();
            
            $table->index(['warranty_claim_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claim_attachments');
    }
};