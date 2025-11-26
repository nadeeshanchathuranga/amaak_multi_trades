<?php

// Test script to verify return flow
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TESTING RETURN FLOW ===\n\n";

// 1. Check sale_items before return
$saleItem = App\Models\SaleItem::first();
if (!$saleItem) {
    echo "❌ No sale items found. Please create a sale first.\n";
    exit;
}

echo "📊 BEFORE RETURN:\n";
echo "Sale Item ID: {$saleItem->id}\n";
echo "Product: {$saleItem->product->name}\n";
echo "Quantity: {$saleItem->quantity}\n";
echo "Total Price: {$saleItem->total_price}\n";
echo "Unit Price: {$saleItem->unit_price}\n\n";

// 2. Check P2P transactions table
try {
    $p2pCount = App\Models\P2PReturnTransaction::count();
    echo "✅ P2P Transactions Table: EXISTS\n";
    echo "Current P2P transactions: {$p2pCount}\n\n";
} catch (Exception $e) {
    echo "❌ P2P Transactions Table Error: {$e->getMessage()}\n\n";
}

// 3. Check return items
$returnCount = App\Models\ReturnItem::where('sale_item_id', $saleItem->id)->count();
echo "Previous returns for this item: {$returnCount}\n";

if ($returnCount > 0) {
    $returns = App\Models\ReturnItem::where('sale_item_id', $saleItem->id)->get();
    foreach ($returns as $ret) {
        echo "  - Returned: {$ret->quantity} units on {$ret->return_date}\n";
    }
}

echo "\n";
echo "=== INSTRUCTIONS ===\n";
echo "1. Go to POS system\n";
echo "2. Process a return for Sale ID: {$saleItem->sale_id}\n";
echo "3. Return 6 units from this sale item\n";
echo "4. Run this script again to verify:\n";
echo "   php test_return_flow.php\n\n";

echo "Expected after returning 6 units:\n";
echo "  - Quantity: " . ($saleItem->quantity - 6) . "\n";
echo "  - Total Price: " . ($saleItem->total_price - (6 * $saleItem->unit_price)) . "\n";
