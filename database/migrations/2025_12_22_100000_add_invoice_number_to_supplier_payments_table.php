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
        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->string('invoice_number')->nullable()->after('supplier_id');
            $table->text('description')->nullable()->after('invoice_number');
            
            // Drop the stored computed column first
            $table->dropColumn('balance');
            
            // Modify total_cost to be nullable since we won't auto-calculate from products
            $table->decimal('total_cost', 12, 2)->nullable()->change();
        });
        
        // Add balance back as a regular computed column
        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->decimal('balance', 12, 2)->storedAs('COALESCE(total_cost, 0) - pay')->after('pay');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->dropColumn(['invoice_number', 'description']);
            
            // Drop the modified balance column
            $table->dropColumn('balance');
            
            // Restore total_cost to not nullable
            $table->decimal('total_cost', 12, 2)->change();
        });
        
        // Restore original balance column
        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->decimal('balance', 12, 2)->storedAs('total_cost - pay')->after('pay');
        });
    }
};