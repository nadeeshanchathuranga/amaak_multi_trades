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
        Schema::table('sales', function (Blueprint $table) {
            $table->enum('custom_discount_type', ['percent', 'fixed'])->nullable()->after('custom_discount');
            $table->decimal('custom_discount_percent', 10, 2)->nullable()->default(0)->after('custom_discount_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['custom_discount_type', 'custom_discount_percent']);
        });
    }
};
