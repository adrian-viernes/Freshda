<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sign-up is vendor-only now, so every account becomes a vendor: both the
     * accounts that were created through the old vendor/customer picker and
     * the column default that future rows fall back to.
     */
    public function up(): void
    {
        DB::table('users')->where('role', 'customer')->update(['role' => 'vendor']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('vendor')->change();
        });
    }

    /**
     * Restore the "customer" column default. The converted accounts stay
     * vendors — their original role was overwritten and is not recorded.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('customer')->change();
        });
    }
};
