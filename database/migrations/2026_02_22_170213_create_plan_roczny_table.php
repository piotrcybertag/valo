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
        Schema::create('plan_roczny', function (Blueprint $table) {
            $table->id();
            $table->decimal('sales_plan', 15, 2)->nullable();
            $table->decimal('cos_plan', 15, 2)->nullable();
            $table->decimal('direct_plan', 15, 2)->nullable();
            $table->decimal('indirect_plan', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_roczny');
    }
};
