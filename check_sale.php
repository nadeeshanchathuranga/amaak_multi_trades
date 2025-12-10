<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Sale;

echo "=== Checking Sale Order #CH/25.12.10/0026 ===\n\n";

$sale = Sale::with(['saleItems.product'])->where('order_id', 'CH/25.12.10/0026')->first();

if (!$sale) {
    echo "Sale not found!\n";
    exit;
}

echo "Sale ID: {$sale->id}\n";
echo "Order ID: {$sale->order_id}\n";
echo "Sale Discount: {$sale->discount} LKR\n";
echo "Sale Total: {$sale->total_amount} LKR\n";
echo "\nSale Items:\n";
echo str_repeat("-", 80) . "\n";

foreach ($sale->saleItems as $item) {
    echo "Product: {$item->product->name}\n";
    echo "  Quantity: {$item->quantity}\n";
    echo "  Unit Price: {$item->unit_price} LKR\n";
    echo "  Discount: {$item->discount} LKR\n";
    echo "  Total Price: {$item->total_price} LKR\n";
    echo "  Expected (Qty × Unit): " . ($item->quantity * $item->unit_price) . " LKR\n";
    echo "\n";
}

echo str_repeat("-", 80) . "\n";
echo "Analysis:\n";
$totalBeforeDiscount = $sale->saleItems->sum(function($item) {
    return $item->quantity * $item->unit_price;
});
echo "Sum of (Quantity × Unit Price): {$totalBeforeDiscount} LKR\n";
echo "Sum of Sale Item Totals: " . $sale->saleItems->sum('total_price') . " LKR\n";
echo "Sum of Sale Item Discounts: " . $sale->saleItems->sum('discount') . " LKR\n";
echo "\nThe issue: Unit Price is still showing original price (1000) instead of discounted price (900)\n";
