<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_roczny_grupa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupa_id')->constrained('grupy')->cascadeOnDelete();
            $table->decimal('sales_plan', 15, 2)->nullable();
            $table->decimal('cos_plan', 15, 2)->nullable();
            $table->decimal('direct_plan', 15, 2)->nullable();
            $table->timestamps();
            $table->unique('grupa_id');
        });

        $colsToDrop = [];
        foreach (['sales_plan', 'cos_plan', 'margin1_plan', 'direct_plan', 'operational_result_plan', 'indirect_plan', 'ebit_plan', 'financial_plan', 'income_plan'] as $col) {
            if (Schema::hasColumn('plan_roczny', $col)) {
                $colsToDrop[] = $col;
            }
        }
        if (! empty($colsToDrop)) {
            Schema::table('plan_roczny', fn (Blueprint $t) => $t->dropColumn($colsToDrop));
        }
        if (! Schema::hasColumn('plan_roczny', 'direct_ogolne_plan')) {
            Schema::table('plan_roczny', function (Blueprint $table) {
                $table->decimal('direct_ogolne_plan', 15, 2)->nullable();
            });
        }
        if (! Schema::hasColumn('plan_roczny', 'indirect_plan')) {
            Schema::table('plan_roczny', function (Blueprint $table) {
                $table->decimal('indirect_plan', 15, 2)->nullable();
            });
        }
        if (! Schema::hasColumn('plan_roczny', 'finansowe_ogolne_plan')) {
            Schema::table('plan_roczny', function (Blueprint $table) {
                $table->decimal('finansowe_ogolne_plan', 15, 2)->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('plan_roczny', function (Blueprint $table) {
            $table->dropColumn(['direct_ogolne_plan', 'indirect_plan', 'finansowe_ogolne_plan']);
        });
        Schema::table('plan_roczny', function (Blueprint $table) {
            $table->decimal('sales_plan', 15, 2)->nullable();
            $table->decimal('cos_plan', 15, 2)->nullable();
            $table->decimal('direct_plan', 15, 2)->nullable();
            $table->decimal('indirect_plan', 15, 2)->nullable();
        });
        Schema::dropIfExists('plan_roczny_grupa');
    }
};
