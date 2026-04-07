<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_roczny', function (Blueprint $table) {
            $table->decimal('margin1_plan', 15, 2)->nullable()->after('cos_plan');
            $table->decimal('operational_result_plan', 15, 2)->nullable()->after('direct_plan');
            $table->decimal('ebit_plan', 15, 2)->nullable()->after('indirect_plan');
            $table->decimal('financial_plan', 15, 2)->nullable()->after('ebit_plan');
            $table->decimal('income_plan', 15, 2)->nullable()->after('financial_plan');
        });
    }

    public function down(): void
    {
        Schema::table('plan_roczny', function (Blueprint $table) {
            $table->dropColumn(['margin1_plan', 'operational_result_plan', 'ebit_plan', 'financial_plan', 'income_plan']);
        });
    }
};
