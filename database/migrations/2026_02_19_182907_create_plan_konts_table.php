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
        Schema::create('plan_konts', function (Blueprint $table) {
            $table->id();
            $table->string('nr')->nullable();
            $table->string('grupa')->nullable();
            $table->string('nazwa')->nullable();
            $table->string('rodzaj_pozycji')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_konts');
    }
};
