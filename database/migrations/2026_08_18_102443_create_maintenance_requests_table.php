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
     * Only "description" and two foreign key are known when submitted.
     * Everything else is what the Agent will fill in after reviewing the request.
     */
    public function up(): void
    {
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->text('description');
            $table->string('category')->nullable();
            $table->string('urgency')->nullable();
            $table->string('status')->default('submitted');
            $table->string('responsibility')->nullable();
            $table->decimal('estimated_hours', 5, 2)->nullable();
            $table->decimal('estimated_cost', 10, 2)->nullable();

            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};
