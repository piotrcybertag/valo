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
        Schema::table('imports', function (Blueprint $table) {
            if (!Schema::hasColumn('imports', 'data_okresu')) {
                $table->date('data_okresu')->nullable()->after('id');
            }
            if (!Schema::hasColumn('imports', 'nazwa_pliku')) {
                $table->string('nazwa_pliku')->nullable()->after('data_okresu');
            }
        });
    }

    public function down(): void
    {
        Schema::table('imports', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('imports', 'data_okresu')) {
                $cols[] = 'data_okresu';
            }
            if (Schema::hasColumn('imports', 'nazwa_pliku')) {
                $cols[] = 'nazwa_pliku';
            }
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
