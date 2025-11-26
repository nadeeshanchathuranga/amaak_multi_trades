# Return System Documentation

## Overview
This document describes the comprehensive return system implemented for the POS application, supporting both Cash Returns and Product-to-Product (P2P) Returns with automatic commission adjustments.

## Features Implemented

### 1. Return Type Handling
- **Cash Return**: Deducts the returned amount from the total bill automatically
- **Product-to-Product (P2P) Return**: Allows exchanging returned products with new products, adjusting commissions accordingly

### 2. Quantity Management
- Tracks returned quantities per sale item using `sale_item_id`
- Prevents returning more items than originally purchased
- Displays available quantity for returns in real-time
- Example: If original quantity = 10 and 2 units are returned, only 8 units remain for future returns

### 3. Cash Returns
- Automatically deducts the returned amount from the sale's total
- Updates stock quantity (adds back returned items)
- Creates stock transaction records
- Adjusts employee commissions proportionally

### 4. Product-to-Product (P2P) Returns
- Allows selecting a new product to exchange
- Reduces commission for the returned product
- Adds commission for the newly received product
- Updates bill total: `New Total = Old Total - Return Amount + New Product Amount`
- Manages stock for both returned and new products

### 5. Employee Commission Management
- Automatically fetches employee information when a bill is selected
- Adjusts commissions based on return transactions:
  - For Cash Returns: Reduces commission proportionally
  - For P2P Returns: Removes old product commission, adds new product commission
- Uses `sale_item_id` for accurate commission tracking

### 6. Save/Update Functionality
Updates on save:
- Remaining quantities per sale item
- Bill total amount
- Return amount tracking
- Employee commissions (automatic calculation)
- Stock quantities
- Stock transactions

### 7. Data Integrity
- Prevents errors if multiple returns are made for the same product
- Validates return quantities against available quantities
- Transaction-based operations (rollback on errors)
- Foreign key relationships ensure data consistency

## Database Schema

### New Columns in `return_items` Table
- `return_type` (enum: 'cash', 'p2p') - Type of return
- `new_product_id` (foreign key) - Product received in P2P return
- `employee_id` (foreign key) - Employee who handled the original sale
- `new_product_amount` (decimal) - Amount of new product in P2P
- `original_quantity` (integer) - Original sale item quantity
- `sale_item_id` (foreign key) - Links to specific sale item for accurate tracking

## API Endpoints

### Fetch Sale Items with Remaining Quantities
```
POST /api/sale/items
Body: { sale_id: <sale_id> }
Response: {
  saleItems: [...],
  employee: {...}
}
```

### Submit Return
```
POST /return-bill
Body: {
  return_items: [{
    sale_id: <id>,
    sale_item_id: <id>,
    product_id: <id>,
    quantity: <number>,
    reason: <string>,
    return_date: <date>,
    return_type: 'cash' | 'p2p',
    unit_price: <number>,
    new_product_id: <id> (if P2P),
    new_product_amount: <number> (if P2P)
  }]
}
```

## UI Components

### Return Bill Modal
Located in: `resources/js/Pages/Pos/Index.vue`

Features:
- Order selection dropdown
- Employee name display (auto-fetched)
- Return type selection (Cash/P2P) per item
- New product selection for P2P returns
- Quantity increment/decrement with max validation
- Real-time total calculation
- Reason and date inputs
- Validation messages

### Table Columns
1. Product (with image and available quantity)
2. Quantity (with +/- buttons)
3. Unit Price
4. Total Price (calculated dynamically)
5. Return Type (Cash/P2P dropdown)
6. New Product (P2P only - product selector + amount)
7. Reason (textarea)
8. Return Date (date picker)
9. Action (remove button)

## Business Logic Flow

### Cash Return Flow
1. User selects order and products to return
2. Selects "Cash Return" as return type
3. Enters quantity, reason, and date
4. System validates remaining quantity
5. On save:
   - Creates return_item record
   - Updates product stock (+returned quantity)
   - Creates stock transaction (type: 'Returned')
   - Adjusts employee commission (reduces proportionally)
   - Updates sale total amount (-return amount)

### P2P Return Flow
1. User selects order and products to return
2. Selects "Product-to-Product (P2P)" as return type
3. Selects new product and enters its amount
4. Enters quantity, reason, and date
5. System validates:
   - Remaining quantity for return
   - New product stock availability
6. On save:
   - Creates return_item record with new_product_id
   - Updates returned product stock (+returned quantity)
   - Updates new product stock (-1)
   - Creates stock transactions for both products
   - Removes/adjusts commission for returned product
   - Adds commission for new product
   - Updates sale total: `New Total = Old - Return + New Product`

## Commission Calculation

### For Returns
```php
// Get total returned for this sale item
$totalReturned = ReturnItem::where('sale_item_id', $saleItem->id)->sum('quantity');

// Calculate remaining
$remainingQty = $originalQty - $totalReturned;

if ($remainingQty <= 0) {
    // Delete commission (fully returned)
} else {
    // Recalculate commission for remaining quantity
    $newTotal = $unitPrice * $remainingQty;
    $newCommission = $newTotal * ($commissionRate / 100);
}
```

### For P2P New Products
```php
if ($category->commission > 0) {
    $commissionAmount = $newProductAmount * ($category->commission / 100);
    EmployeeCommission::create([
        'employee_id' => $sale->employee_id,
        'product_id' => $newProductId,
        'commission_amount' => $commissionAmount,
        // ... other fields
    ]);
}
```

## Validation Rules

### Frontend Validation
- All items must have a reason
- P2P returns must have a new product selected
- Return quantity cannot exceed remaining quantity
- Return date is required

### Backend Validation
- `sale_id` must exist
- `sale_item_id` must exist
- `product_id` must exist
- `quantity` must be >= 1
- `return_type` must be 'cash' or 'p2p'
- `new_product_id` required if return_type is 'p2p'
- Returned quantity cannot exceed available quantity

## Error Handling

### Common Errors
- "Cannot return X units. Only Y units available for return."
- "New product 'Product Name' is out of stock."
- "Please provide a reason for all return items"
- "Please select a new product for P2P returns"

### Transaction Rollback
All operations are wrapped in database transactions. If any error occurs, all changes are rolled back to maintain data integrity.

## Testing Checklist

- [ ] Cash return with partial quantity
- [ ] Cash return with full quantity
- [ ] P2P return with new product
- [ ] Multiple returns for same sale item
- [ ] Return validation (exceeding available quantity)
- [ ] Commission adjustment for cash returns
- [ ] Commission adjustment for P2P returns
- [ ] Stock quantity updates
- [ ] Sale total updates
- [ ] Employee information display
- [ ] Real-time quantity tracking
- [ ] Error messages display correctly

## Models Updated

1. **ReturnItem** (`app/Models/ReturnItem.php`)
   - Added new fillable fields
   - Added relationships: `saleItem`, `newProduct`, `employee`
   - Added `getRemainingQuantity()` method

2. **EmployeeCommission** (`app/Models/EmployeeCommission.php`)
   - Existing model used for commission tracking

3. **SaleItem** (`app/Models/SaleItem.php`)
   - Existing model, no changes needed

## Controllers Updated

1. **ReturnItemController** (`app/Http/Controllers/ReturnItemController.php`)
   - `fetchSaleItems()`: Returns items with remaining quantities
   - `store()`: Handles both cash and P2P returns
   - `adjustEmployeeCommissions()`: Private method for commission updates

## Migration Files

1. `2025_11_24_170954_add_return_type_and_tracking_to_return_items_table.php`
   - Adds return_type, new_product_id, employee_id, new_product_amount, original_quantity

2. `2025_11_24_172753_add_sale_item_id_to_return_items_table.php`
   - Adds sale_item_id for precise tracking

## Future Enhancements

1. Return history report per employee
2. Return analytics dashboard
3. Automatic refund processing
4. Return approval workflow
5. Print return receipt
6. Email notification for returns
7. Return reason analytics
8. Batch return processing

## Support

For questions or issues, contact the development team.
