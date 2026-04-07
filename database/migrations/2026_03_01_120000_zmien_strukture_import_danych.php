<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_danych', function (Blueprint $table) {
            $table->dropColumn(['grupa', 'nazwa', 'rodzaj_pozycji', 'wn1', 'ma1', 'wn', 'ma']);
        });
        Schema::table('import_danych', function (Blueprint $table) {
            $table->decimal('wn4', 15, 2)->default(0)->after('nr');
            $table->decimal('ma4', 15, 2)->default(0)->after('wn4');
            $table->decimal('wn5', 15, 2)->default(0)->after('ma4');
            $table->decimal('ma5', 15, 2)->default(0)->after('wn5');
        });
    }

    public function down(): void
    {
        Schema::table('import_danych', function (Blueprint $table) {
            $table->dropColumn(['wn4', 'ma4', 'wn5', 'ma5']);
        });
        Schema::table('import_danych', function (Blueprint $table) {
            $table->string('grupa')->nullable()->after('nr');
            $table->string('nazwa')->nullable()->after('grupa');
            $table->string('rodzaj_pozycji')->nullable()->after('nazwa');
            $table->decimal('wn1', 15, 2)->default(0)->after('rodzaj_pozycji');
            $table->decimal('ma1', 15, 2)->default(0)->after('wn1');
            $table->decimal('wn', 15, 2)->default(0)->after('ma1');
            $table->decimal('ma', 15, 2)->default(0)->after('wn');
        });
    }
};
