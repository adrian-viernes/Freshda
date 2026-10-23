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
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('fish_type');
            $table->date('delivery_date');
            $table->decimal('expected_quantity', 8, 2);
            /* Recorded when the delivery is physically checked in. */
            $table->decimal('actual_quantity', 8, 2)->nullable();
            $table->string('status')->default('pending');
            $table->text('inspection_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('accepted_at')->nullable();
            /* The stock movement an acceptance produced, plus the moment it
               ran — the pair is what stops a delivery adding its quantity to
               the inventory twice. */
            $table->foreignId('inventory_item_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('stock_updated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
