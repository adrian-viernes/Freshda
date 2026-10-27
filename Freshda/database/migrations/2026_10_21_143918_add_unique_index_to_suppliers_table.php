<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two tabs racing to register the same supplier name could both pass the
 * controller's pre-check; the database has the final say. Names stay unique
 * per vendor — the same rule the validation already enforces.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->unique(['user_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'name']);
        });
    }
};
