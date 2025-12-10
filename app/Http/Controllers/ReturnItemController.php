<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ReturnItem;
use App\Models\Product;
use App\Models\Employee;
use App\Models\EmployeeCommission;
use App\Models\StockTransaction;
use App\Models\P2PReturnTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReturnItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!Gate::allows('hasRole', ['Admin', 'Manager'])) {
            abort(403, 'Unauthorized');
        }

        $sales = Sale::with('customer', 'employee')->orderBy('created_at', 'desc')->get();
        $saleItems = SaleItem::with('product')->orderBy('created_at', 'desc')->get();

        return Inertia::render('ReturnItem/Index', [
            'sales' => $sales,
            'saleItems' => $saleItems,
        ]);
    }

    public function fetchSaleItems(Request $request)
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
        ]);

        $sale = Sale::with('employee')->find($request->input('sale_id'));
        
        $saleItems = SaleItem::with('product')
            ->where('sale_id', $request->input('sale_id'))
            ->get()
            ->map(function ($item) {
                // Calculate already returned quantity using sale_item_id
                $returnedQty = ReturnItem::where('sale_item_id', $item->id)
                    ->sum('quantity');
                
                // Note: sale_items.quantity gets reduced after each return
                // So original_quantity = current_quantity + already_returned
                $originalQuantity = $item->quantity + $returnedQty;
                
                $item->returned_quantity = $returnedQty;
                $item->remaining_quantity = $item->quantity; // Current quantity IS the remaining quantity
                $item->original_sale_quantity = $originalQuantity;
                
                return $item;
            });

        return response()->json([
            'saleItems' => $saleItems,
            'employee' => $sale->employee,
        ]);
    }

    /**
     * Store a newly created return item.
     * Creates separate sale/bill for P2P returns
     */
    public function store(Request $request)
    {
        if (!Gate::allows('hasRole', ['Admin', 'Manager', 'Cashier'])) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'return_items' => 'required|array',
            'return_items.*.sale_id' => 'required|exists:sales,id',
            'return_items.*.sale_item_id' => 'required|exists:sale_items,id',
            'return_items.*.product_id' => 'required|exists:products,id',
            'return_items.*.quantity' => 'required|integer|min:1',
            'return_items.*.reason' => 'required|string',
            'return_items.*.return_date' => 'required|date',
            'return_items.*.return_type' => 'required|in:cash,p2p',
            'return_items.*.unit_price' => 'required|numeric|min:0',
            // For P2P returns
            'new_products' => 'nullable|array',
            'new_products.*.product_id' => 'required_with:new_products|exists:products,id',
            'new_products.*.quantity' => 'required_with:new_products|integer|min:1',
            'new_products.*.selling_price' => 'required_with:new_products|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $originalSale = null;
            $returnSale = null;
            $hasP2P = false;
            
            $returnBillData = [
                'return_items' => [],
                'new_products' => [],
                'totals' => [
                    'return_amount' => 0,
                    'new_product_amount' => 0,
                    'net_amount' => 0,
                ]
            ];

            // Check if any P2P returns
            foreach ($validated['return_items'] as $item) {
                if ($item['return_type'] === 'p2p') {
                    $hasP2P = true;
                    break;
                }
            }

            // Process return items
            foreach ($validated['return_items'] as $item) {
                $sale = Sale::find($item['sale_id']);
                $originalSale = $sale;
                $saleItem = SaleItem::find($item['sale_item_id']);

                if (!$saleItem) {
                    throw new \Exception("Sale item not found with ID {$item['sale_item_id']}");
                }

                // Calculate remaining quantity available for return
                // Note: sale_items.quantity gets reduced on each return, so we need to add back
                // already returned quantity to get the original quantity
                $alreadyReturned = ReturnItem::where('sale_item_id', $item['sale_item_id'])
                    ->sum('quantity');

                // Original quantity = current quantity + already returned quantity
                $originalQuantity = $saleItem->quantity + $alreadyReturned;
                $remainingQty = $originalQuantity - $alreadyReturned;

                if ($item['quantity'] > $remainingQty) {
                    throw new \Exception("Cannot return {$item['quantity']} units. Only {$remainingQty} units available for return (Original: {$originalQuantity}, Already returned: {$alreadyReturned}).");
                }

                // Calculate proportional discount for the returned quantity
                // Get the original sale item discount and calculate per-unit discount
                $originalSaleItemDiscount = $saleItem->discount ?? 0;
                $totalReturnedDiscount = ReturnItem::where('sale_item_id', $item['sale_item_id'])
                    ->sum('discount');
                
                // Remaining discount = original discount - already returned discount
                $remainingDiscount = $originalSaleItemDiscount - $totalReturnedDiscount;
                
                // Calculate per-unit discount based on remaining quantity
                $perUnitDiscount = $remainingQty > 0 ? ($remainingDiscount / $remainingQty) : 0;
                
                // Proportional discount for this return
                $returnDiscount = $perUnitDiscount * $item['quantity'];

                // Calculate return amounts
                $returnSubtotal = $item['quantity'] * $item['unit_price'];
                $returnAmount = $returnSubtotal - $returnDiscount;
                
                $returnBillData['totals']['return_amount'] += $returnAmount;

                // Increase stock for returned product
                $returnedProduct = Product::find($item['product_id']);
                $returnedProduct->update([
                    'stock_quantity' => $returnedProduct->stock_quantity + $item['quantity']
                ]);

                // Create stock transaction
                StockTransaction::create([
                    'product_id' => $item['product_id'],
                    'transaction_type' => 'Returned',
                    'quantity' => $item['quantity'],
                    'transaction_date' => $item['return_date'],
                    'supplier_id' => $returnedProduct->supplier_id ?? null,
                ]);

                // Adjust employee commissions BEFORE updating sale_items
                if ($sale->employee_id) {
                    $this->adjustEmployeeCommissions($sale, $saleItem, $item, $returnedProduct);
                }

                // Update sale_items table: reduce quantity, total_price, and discount
                $saleItem->quantity -= $item['quantity'];
                $saleItem->total_price -= $returnAmount;
                $saleItem->discount -= $returnDiscount;
                $saleItem->save();

                // Update original sale total (deduct returned amount) and discount
                $sale->total_amount -= $returnAmount;
                $sale->discount -= $returnDiscount;
                $sale->save();

                // Create return item record
                ReturnItem::create([
                    'sale_id' => $item['sale_id'],
                    'sale_item_id' => $item['sale_item_id'],
                    'customer_id' => $sale->customer_id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'reason' => $item['reason'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $returnAmount,
                    'discount' => $returnDiscount,
                    'return_date' => $item['return_date'],
                    'return_type' => $item['return_type'],
                    'employee_id' => $sale->employee_id,
                    'original_quantity' => $saleItem->quantity,
                ]);

                // Add to return bill data
                $returnBillData['return_items'][] = [
                    'product_id' => $item['product_id'],
                    'product_name' => $returnedProduct->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $returnSubtotal,
                    'discount' => $returnDiscount,
                    'total' => $returnAmount,
                    'return_type' => $item['return_type'],
                    'reason' => $item['reason'],
                ];
            }

            // For P2P returns, create a separate sale/bill with new products
            if ($hasP2P && !empty($validated['new_products'])) {
                $newProductsTotal = 0;
                
                // Generate unique order ID for return bill
                $returnOrderId = 'RTN-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));

                // Calculate total for new products
                foreach ($validated['new_products'] as $newProductData) {
                    $newProductTotal = $newProductData['quantity'] * $newProductData['selling_price'];
                    $newProductsTotal += $newProductTotal;
                }

                // Create a new Sale record for the return transaction (P2P bill)
                $returnSale = Sale::create([
                    'customer_id' => $originalSale->customer_id,
                    'employee_id' => $originalSale->employee_id,
                    'user_id' => $originalSale->user_id,
                    'order_id' => $returnOrderId,
                    'total_amount' => $newProductsTotal,
                    'discount' => 0,
                    'payment_method' => $originalSale->payment_method,
                    'sale_date' => now()->toDateString(),
                    'total_cost' => 0, // Will be calculated from new products
                    'cash' => 0,
                    'custom_discount' => 0,
                ]);

                // Process new products and create sale items
                foreach ($validated['new_products'] as $newProductData) {
                    $newProduct = Product::find($newProductData['product_id']);
                    
                    // Check stock availability
                    if ($newProduct->stock_quantity < $newProductData['quantity']) {
                        throw new \Exception("Insufficient stock for product: {$newProduct->name}. Available: {$newProduct->stock_quantity}");
                    }

                    $newProductTotal = $newProductData['quantity'] * $newProductData['selling_price'];
                    $returnBillData['totals']['new_product_amount'] += $newProductTotal;

                    // Calculate item discount (proportional if sale has discount)
                    $itemDiscount = 0;
                    if ($returnSale->discount > 0 && $newProductsTotal > 0) {
                        $itemDiscount = ($newProductTotal / $newProductsTotal) * $returnSale->discount;
                    }
                    $perUnitDiscount = $newProductData['quantity'] > 0 ? ($itemDiscount / $newProductData['quantity']) : 0;
                    $discountedUnitPrice = $newProductData['selling_price'] - $perUnitDiscount;
                    $itemFinalTotal = $newProductTotal - $itemDiscount;

                    // Create sale item for new product in return bill
                    $newSaleItem = SaleItem::create([
                        'sale_id' => $returnSale->id,
                        'product_id' => $newProduct->id,
                        'quantity' => $newProductData['quantity'],
                        'unit_price' => $discountedUnitPrice, // Store discounted unit price
                        'total_price' => $itemFinalTotal,
                        'discount' => $itemDiscount,
                    ]);

                    // Reduce stock for new product
                    $newProduct->update([
                        'stock_quantity' => $newProduct->stock_quantity - $newProductData['quantity']
                    ]);

                    // Create stock transaction
                    StockTransaction::create([
                        'product_id' => $newProduct->id,
                        'transaction_type' => 'Sold',
                        'quantity' => $newProductData['quantity'],
                        'transaction_date' => now(),
                        'supplier_id' => $newProduct->supplier_id ?? null,
                    ]);

                    // Add commission for new product
                    if ($returnSale->employee_id && $newProduct->category_id) {
                        $category = \App\Models\Category::find($newProduct->category_id);
                        if ($category && $category->commission > 0) {
                            $commissionAmount = EmployeeCommission::calculateCommission(
                                $newProductTotal,
                                $category->commission
                            );

                            EmployeeCommission::create([
                                'employee_id' => $returnSale->employee_id,
                                'sale_id' => $returnSale->id,
                                'sale_item_id' => $newSaleItem->id,
                                'product_id' => $newProduct->id,
                                'category_id' => $newProduct->category_id,
                                'commission_percentage' => $category->commission,
                                'product_price' => $newProductData['selling_price'],
                                'quantity' => $newProductData['quantity'],
                                'total_product_amount' => $newProductTotal,
                                'commission_amount' => $commissionAmount,
                                'commission_date' => now(),
                            ]);
                        }
                    }

                    // Add to return bill data
                    $returnBillData['new_products'][] = [
                        'product_id' => $newProduct->id,
                        'product_name' => $newProduct->name,
                        'quantity' => $newProductData['quantity'],
                        'unit_price' => $newProductData['selling_price'],
                        'total' => $newProductTotal,
                    ];
                }

                // Create P2P Return Transaction record
                foreach ($validated['return_items'] as $item) {
                    $returnedProduct = Product::find($item['product_id']);
                    $returnAmount = $item['quantity'] * $item['unit_price'];
                    
                    foreach ($validated['new_products'] as $newProductData) {
                        $newProduct = Product::find($newProductData['product_id']);
                        $newProductTotal = $newProductData['quantity'] * $newProductData['selling_price'];
                        
                        \App\Models\P2PReturnTransaction::create([
                            'original_sale_id' => $originalSale->id,
                            'return_sale_id' => $returnSale->id,
                            'customer_id' => $originalSale->customer_id,
                            'employee_id' => $originalSale->employee_id,
                            'transaction_type' => 'p2p',
                            'returned_product_id' => $returnedProduct->id,
                            'returned_quantity' => $item['quantity'],
                            'returned_unit_price' => $item['unit_price'],
                            'returned_total_amount' => $returnAmount,
                            'new_product_id' => $newProduct->id,
                            'new_product_quantity' => $newProductData['quantity'],
                            'new_product_unit_price' => $newProductData['selling_price'],
                            'new_product_total_amount' => $newProductTotal,
                            'net_amount' => $newProductTotal - $returnAmount,
                            'reason' => $item['reason'],
                            'return_date' => $item['return_date'],
                            'status' => 'completed',
                        ]);
                    }
                }
            }

            // Calculate net amount
            $returnBillData['totals']['net_amount'] = 
                $returnBillData['totals']['new_product_amount'] - $returnBillData['totals']['return_amount'];

            DB::commit();

            // Prepare return sale data for print bill
            // For P2P: shows new products issued
            // For Cash: can show return receipt details
            $returnSaleData = null;
            if ($returnSale) {
                $returnSale->load(['items.product.unit', 'customer', 'employee']);
                $returnSaleData = [
                    'id' => $returnSale->id,
                    'order_id' => $returnSale->order_id,
                    'total_amount' => $returnSale->total_amount,
                    'payment_method' => $returnSale->payment_method,
                    'sale_date' => $returnSale->sale_date,
                    'customer' => $returnSale->customer,
                    'employee' => $returnSale->employee,
                    'items' => $returnSale->items->map(function($item) {
                        return [
                            'id' => $item->id,
                            'name' => $item->product->name,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'total_price' => $item->total_price,
                            'unit' => $item->product->unit,
                            'discount' => 0,
                            'apply_discount' => false,
                        ];
                    }),
                ];
            }

            // Prepare cash return receipt data (for cash-only returns)
            $cashReturnData = null;
            if (!$hasP2P && $originalSale && count($returnBillData['return_items']) > 0) {
                $originalSale->load(['customer', 'employee']);
                
                // Format return items for receipt display
                $formattedReturnItems = collect($returnBillData['return_items'])->map(function($item) {
                    return [
                        'id' => $item['product_id'],
                        'name' => $item['product_name'] . ' (RETURN)',
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'selling_price' => $item['unit_price'],
                        'total_price' => $item['total'],
                        'discount' => 0,
                        'apply_discount' => false,
                    ];
                })->toArray();

                $cashReturnData = [
                    'id' => $originalSale->id,
                    'order_id' => $originalSale->order_id . '-RETURN',
                    'total_amount' => $returnBillData['totals']['return_amount'],
                    'payment_method' => 'Cash Return',
                    'sale_date' => now()->toDateString(),
                    'customer' => $originalSale->customer,
                    'employee' => $originalSale->employee,
                    'return_items' => $formattedReturnItems,
                    'original_order_id' => $originalSale->order_id,
                ];
            }

            return response()->json([
                'message' => 'Return processed successfully!',
                'success' => true,
                'return_bill_data' => $returnBillData,
                'original_sale_id' => $originalSale ? $originalSale->id : null,
                'return_sale_id' => $returnSale ? $returnSale->id : null,
                'return_order_id' => $returnSale ? $returnSale->order_id : null,
                'return_sale_data' => $returnSaleData,
                'cash_return_data' => $cashReturnData,
                'is_p2p' => $hasP2P,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'An error occurred while processing the return.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Adjust employee commissions for returned items
     */
    private function adjustEmployeeCommissions($sale, $saleItem, $returnData, $returnedProduct)
    {
        // Find commission for this specific sale item
        $commission = EmployeeCommission::where('sale_id', $sale->id)
            ->where('sale_item_id', $saleItem->id)
            ->first();

        if (!$commission) {
            return; // No commission to adjust
        }

        // Calculate the new quantity after THIS specific return
        $currentReturnQty = $returnData['quantity'];
        $newQuantity = $commission->quantity - $currentReturnQty;

        if ($newQuantity <= 0) {
            // Fully returned, delete commission
            $commission->delete();
        } else {
            // Partially returned, recalculate commission for remaining quantity
            $newTotalAmount = $commission->product_price * $newQuantity;
            $newCommissionAmount = EmployeeCommission::calculateCommission(
                $newTotalAmount,
                $commission->commission_percentage
            );

            $commission->update([
                'quantity' => $newQuantity,
                'total_product_amount' => $newTotalAmount,
                'commission_amount' => $newCommissionAmount,
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ReturnItem $returnItem)
    {
        return response()->json($returnItem->load(['sale', 'customer', 'product', 'newProduct', 'employee']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ReturnItem $returnItem)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReturnItem $returnItem)
    {
        //
    }
}
