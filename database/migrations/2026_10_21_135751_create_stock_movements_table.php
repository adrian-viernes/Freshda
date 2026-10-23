<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every quantity change leaves a row here, so the vendor can always answer
 * "why does the item read 13 kg?". Item names are snapshotted because an
 * inventory row may be deleted later, which would orphan its history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_name');
            $table->decimal('change', 8, 2);
            $table->decimal('balance_after', 8, 2)->nullable();
            $table->string('type', 20);
            $table->string('reference')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
