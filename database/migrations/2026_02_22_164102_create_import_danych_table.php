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
        if (Schema::hasTable('import_danych')) {
            return;
        }
        Schema::create('import_danych', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('imports')->cascadeOnDelete();
            $table->string('nr')->nullable();
            $table->string('grupa')->nullable();
            $table->string('nazwa')->nullable();
            $table->string('rodzaj_pozycji')->nullable();
            $table->decimal('wn1', 15, 2)->default(0);
            $table->decimal('ma1', 15, 2)->default(0);
            $table->decimal('wn', 15, 2)->default(0);
            $table->decimal('ma', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_danych');
    }
};
