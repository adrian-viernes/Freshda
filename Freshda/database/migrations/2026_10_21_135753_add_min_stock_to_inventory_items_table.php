<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-item low-stock threshold. NULL keeps the shared default
 * (InventoryItem::LOW_STOCK_THRESHOLD), so existing rows behave unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->decimal('min_stock', 8, 2)->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn('min_stock');
        });
    }
};
