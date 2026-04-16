<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('import_danych')) {
            return;
        }
        Schema::table('import_danych', function (Blueprint $table) {
            $table->renameColumn('wn4', 'wn2');
            $table->renameColumn('ma4', 'ma2');
            $table->renameColumn('wn5', 'wn3');
            $table->renameColumn('ma5', 'ma3');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('import_danych')) {
            return;
        }
        Schema::table('import_danych', function (Blueprint $table) {
            $table->renameColumn('wn2', 'wn4');
            $table->renameColumn('ma2', 'ma4');
            $table->renameColumn('wn3', 'wn5');
            $table->renameColumn('ma3', 'ma5');
        });
    }
};
