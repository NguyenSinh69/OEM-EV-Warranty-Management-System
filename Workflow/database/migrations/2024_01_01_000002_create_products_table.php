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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('vin')->unique(); // Vehicle Identification Number
            $table->string('model');
            $table->string('brand');
            $table->integer('year');
            $table->decimal('battery_capacity', 8, 2); // kWh
            $table->date('warranty_start_date');
            $table->date('warranty_end_date');
            $table->date('purchase_date');
            $table->string('dealer_id')->nullable();
            $table->json('specifications')->nullable(); // Additional product specs
            $table->timestamps();
            
            $table->index(['vin']);
            $table->index(['brand', 'model']);
            $table->index(['warranty_end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};