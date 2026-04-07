<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_projekty', function (Blueprint $table) {
            $table->id();
            $table->string('nazwa_pliku')->nullable();
            $table->timestamps();
        });

        Schema::create('import_projekt_dane', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_projekt_id')->constrained('import_projekty')->cascadeOnDelete();
            $table->string('nr')->nullable();
            $table->string('nazwa')->nullable();
            $table->json('wartosci')->nullable(); // {"wn1": 123, "ma1": 0, "wn2": 456, ...}
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_projekt_dane');
        Schema::dropIfExists('import_projekty');
    }
};
