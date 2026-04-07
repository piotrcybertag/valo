<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wips', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('rok');
            $table->unsignedTinyInteger('miesiac');
            $table->string('nazwa_projektu');
            $table->decimal('wartosc', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wips');
    }
};
