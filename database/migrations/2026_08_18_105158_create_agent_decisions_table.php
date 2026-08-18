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
        Schema::create('agent_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_request_id')->constrained()->onDelete('cascade');
            $table->json('tool_calls');
            $table->text('reasoning')->nullable();
            $table->decimal('confidence', 3, 2)->nullable();
            $table->string('outcome');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_decisions');
    }
};
