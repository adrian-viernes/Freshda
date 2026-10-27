<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The feedback module was removed from the product: there is no public
 * feedback flow and no vendor feedback inbox any more, so the table goes
 * with it. The earlier feedback migrations stay in history untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('feedbacks');
    }

    public function down(): void
    {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->string('category')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }
};
