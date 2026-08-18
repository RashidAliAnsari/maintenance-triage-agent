<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

    /**
     * restrictOnDelete() means that if a vendor is deleted, 
     * the work order will not be deleted, and the deletion of the vendor 
     * will be restricted if there are any associated work orders. 
     * This is useful for maintaining data integrity and ensuring that work 
     * orders are not accidentally deleted when a vendor is removed from the system.
     * 
     * The index is the one the availability check uses to find the work orders 
     * for a vendor in a given time range.
     */
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->restrictOnDelete();
            $table->datetime('scheduled_for')->nullable();
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['vendor_id', 'scheduled_for']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
