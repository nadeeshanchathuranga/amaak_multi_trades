# Cash Return System - Complete Implementation

## ✅ All Features Implemented

### 1. Load Original Bill ✅
**Location:** Return Bills Modal in POS

**How it works:**
- User enters order code in "Order Code" field
- System searches for the sale by order_id
- Displays all items with quantities and prices
- Shows customer, employee, and payment details

**Code:** `resources/js/Pages/Pos/Index.vue` - Line 1120-1147
```javascript
const response = await axios.post('/api/sale/items', {
    sale_id: newOrderId
});
// Returns all sale items with remaining quantities
```

---

### 2. Display All Items ✅
**Location:** "Items in this Sale" table

**Shows:**
- Product name with image
- Max Quantity available for return
- Unit Price
- "Add to Return" button

**Features:**
- Shows remaining quantity (accounting for previous returns)
- Prevents returning more than available
- Real-time quantity validation

---

### 3. Select Return Quantity ✅
**Location:** Billing Details section (right side)

**Functionality:**
- User can enter return quantity for each item
- Min: 1, Max: remaining quantity
- Shows max quantity allowed
- Validates against available stock

**Code:** `resources/js/Pages/Pos/Index.vue` - Line 188-195
```html
<input type="number" v-model.number="item.return_quantity" 
       min="1" :max="item.remaining_quantity">
```

---

### 4. Confirm Return - Deductions ✅

#### A. Deduct from Original Bill Total ✅
**Code:** `ReturnItemController.php` - Line 175-177
```php
// Update original sale total (deduct returned amount)
$sale->total_amount -= $returnAmount;
$sale->save();
```

**Example:**
- Original Bill: 10,000 LKR
- Return: 6,000 LKR
- **New Total: 4,000 LKR** ✅

---

#### B. Deduct from Sold Quantity ✅
**Code:** `ReturnItemController.php` - Line 171-173
```php
// Update sale_items table: reduce quantity and total_price
$saleItem->quantity -= $item['quantity'];
$saleItem->total_price -= $returnAmount;
$saleItem->save();
```

**Example:**
- Original: 10 units
- Return: 6 units
- **Remaining: 4 units** ✅

---

#### C. Update Employee Commission ✅
**Code:** `ReturnItemController.php` - Line 394-424
```php
private function adjustEmployeeCommissions($sale, $saleItem, $returnData, $returnedProduct)
{
    $commission = EmployeeCommission::where('sale_id', $sale->id)
        ->where('sale_item_id', $saleItem->id)
        ->first();

    if (!$commission) {
        return;
    }

    $currentReturnQty = $returnData['quantity'];
    $newQuantity = $commission->quantity - $currentReturnQty;

    if ($newQuantity <= 0) {
        $commission->delete(); // Fully returned
    } else {
        // Recalculate commission for remaining quantity
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
```

**Example:**
- Original: 10 units, commission = 1,000 LKR
- Return: 6 units
- **New Commission: 400 LKR** (for 4 remaining units) ✅

---

#### D. Increase Stock Quantity ✅
**Code:** `ReturnItemController.php` - Line 152-154
```php
// Increase stock for returned product
$returnedProduct->update([
    'stock_quantity' => $returnedProduct->stock_quantity + $item['quantity']
]);
```

**Example:**
- Stock: 91 units
- Return: 6 units
- **New Stock: 97 units** ✅

---

### 5. Generate Return Bill/Receipt ✅

**NEW FEATURE ADDED!**

**Backend:** `ReturnItemController.php` - Line 368-397
```php
// Prepare cash return receipt data
$cashReturnData = null;
if (!$hasP2P && $originalSale && count($returnBillData['return_items']) > 0) {
    $originalSale->load(['customer', 'employee']);
    
    $formattedReturnItems = collect($returnBillData['return_items'])->map(function($item) {
        return [
            'name' => $item['product_name'] . ' (RETURN)',
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'total_price' => $item['total'],
        ];
    })->toArray();

    $cashReturnData = [
        'order_id' => $originalSale->order_id . '-RETURN',
        'total_amount' => $returnBillData['totals']['return_amount'],
        'payment_method' => 'Cash Return',
        'customer' => $originalSale->customer,
        'employee' => $originalSale->employee,
        'return_items' => $formattedReturnItems,
    ];
}
```

**Frontend:** `resources/js/Pages/Pos/Index.vue` - Line 1025-1047
```javascript
if (cashReturnData) {
    // Show return receipt with print option
    customer.value = cashReturnData.customer;
    employee.value = cashReturnData.employee;
    products.value = cashReturnData.return_items;
    selectedPaymentMethod.value = cashReturnData.payment_method;
    
    // Show success modal with print option
    isSuccessModalOpen.value = true;
}
```

**Result:**
- ✅ Print Receipt button appears
- ✅ Shows all returned items
- ✅ Shows return amount
- ✅ Shows original order ID + "-RETURN"
- ✅ Can send receipt to email
- ✅ Can print receipt

---

## Complete Cash Return Flow

### Step-by-Step Process:

```
1. User clicks "Return Bills" button
   └─> Opens Return Bills Modal

2. User enters Order Code (e.g., CH/25.11.26/0001)
   └─> System loads sale with all items
   └─> Shows: Product, Max Quantity, Unit Price

3. User clicks "Add to Return" for each item
   └─> Item added to Billing Details section
   └─> Can adjust return quantity (1 to max available)
   └─> Shows reason dropdown

4. User fills return details:
   ├─ Return quantity
   ├─ Return reason (e.g., "Defective", "Wrong Item")
   └─ Return date (auto-filled)

5. User clicks "CONFIRM ORDER"
   └─> System validates quantities
   └─> Processes return:
       ├─ Deducts from original bill total ✅
       ├─ Reduces quantity in sale_items ✅
       ├─ Updates employee commission ✅
       ├─ Increases product stock ✅
       ├─ Creates return_items record ✅
       └─ Generates return receipt ✅

6. Return Receipt Modal appears:
   ├─ Order ID: [ORIGINAL]-RETURN
   ├─ Payment Method: Cash Return
   ├─ Returned Items with quantities
   ├─ Total Return Amount
   ├─ Employee & Customer info
   └─ Buttons:
       ├─ Send Receipt to Email
       ├─ Print Receipt ✅
       └─ Close

7. Receipt can be printed showing:
   ├─ Company info
   ├─ Return details
   ├─ Original order reference
   ├─ Items returned
   └─ Amount refunded
```

---

## Example Scenario

### Initial Sale:
```
Order ID: CH/25.11.26/0001
Customer: John Doe
Employee: Jane Smith

Items:
- Orange: 10 units @ 1,000 = 10,000 LKR
- Apple: 5 units @ 500 = 2,500 LKR

Total: 12,500 LKR
Employee Commission: 1,250 LKR (10%)
```

### Cash Return Process:
```
User Action: Return 6 Orange units
Reason: Customer Changed Mind
Return Date: 2025-11-26

System Actions:
1. ✅ Reduces sale_items:
   - Orange: 10 → 4 units
   - Orange total: 10,000 → 4,000 LKR

2. ✅ Updates original sale:
   - Total: 12,500 → 6,500 LKR

3. ✅ Updates commission:
   - Orange commission: 1,000 → 400 LKR
   - Total commission: 1,250 → 650 LKR

4. ✅ Updates stock:
   - Orange: 91 → 97 units

5. ✅ Creates return record:
   - return_items table entry
   - Links to original sale

6. ✅ Generates return receipt:
   - Order ID: CH/25.11.26/0001-RETURN
   - Shows: 6 Orange @ 1,000 = 6,000 LKR
   - Payment: Cash Return
   - Printable ✅
```

### After Return:
```
Original Sale (Updated):
- Orange: 4 units @ 1,000 = 4,000 LKR
- Apple: 5 units @ 500 = 2,500 LKR
- Total: 6,500 LKR
- Commission: 650 LKR

Stock:
- Orange: 97 units (was 91)

Return Receipt Generated:
- Order: CH/25.11.26/0001-RETURN
- Amount: 6,000 LKR refunded
- Printable: YES ✅
```

---

## Database Changes

### Tables Updated:

1. **sales** table:
   - `total_amount` reduced by return amount

2. **sale_items** table:
   - `quantity` reduced by returned quantity
   - `total_price` reduced proportionally

3. **return_items** table (NEW RECORD):
   - sale_id
   - sale_item_id
   - product_id
   - quantity (returned)
   - total_price (return amount)
   - reason
   - return_date
   - return_type: 'cash'

4. **employee_commissions** table:
   - `quantity` reduced
   - `total_product_amount` recalculated
   - `commission_amount` recalculated
   - OR deleted if fully returned

5. **products** table:
   - `stock_quantity` increased

6. **stock_transactions** table (NEW RECORD):
   - transaction_type: 'Returned'
   - quantity (positive)
   - transaction_date

---

## Key Differences: Cash Return vs P2P Return

| Feature | Cash Return | P2P Return |
|---------|-------------|------------|
| **New Sale Created** | ❌ No | ✅ Yes (RTN-XXXXXXXX) |
| **Return Receipt** | ✅ Yes (ORIGINAL-RETURN) | ✅ Yes (RTN-XXXXXXXX) |
| **Original Sale Updated** | ✅ Yes (reduced) | ✅ Yes (reduced) |
| **Stock Returned** | ✅ Yes (increased) | ✅ Yes (increased) |
| **New Stock Issued** | ❌ No | ✅ Yes (decreased) |
| **Commission Reduced** | ✅ Yes | ✅ Yes |
| **Commission Added** | ❌ No | ✅ Yes (for new items) |
| **Print Receipt** | ✅ Yes | ✅ Yes |
| **P2P Transaction Record** | ❌ No | ✅ Yes |

---

## Testing the Cash Return System

### Test Case 1: Partial Return
```
1. Create sale with 10 units
2. Return 6 units (cash return)
3. Verify:
   ✅ Sale total: 10,000 → 4,000
   ✅ Quantity: 10 → 4
   ✅ Commission: 1,000 → 400
   ✅ Stock: +6 units
   ✅ Return receipt generated
   ✅ Can print receipt
```

### Test Case 2: Full Return
```
1. Create sale with 5 units
2. Return all 5 units (cash return)
3. Verify:
   ✅ Sale total: 5,000 → 0
   ✅ Quantity: 5 → 0
   ✅ Commission: DELETED
   ✅ Stock: +5 units
   ✅ Return receipt generated
```

### Test Case 3: Multiple Items Return
```
1. Create sale with 3 different products
2. Return 2 of the products
3. Verify:
   ✅ Each item quantity reduced
   ✅ Sale total reduced correctly
   ✅ Commission for each adjusted
   ✅ Stock for each increased
   ✅ Return receipt shows all items
```

---

## ✅ All Requirements Met

1. ✅ Load original bill by order code
2. ✅ Display all items with quantities and prices
3. ✅ Allow user to choose return quantity
4. ✅ Deduct from original bill total on confirm
5. ✅ Deduct from sold quantity
6. ✅ Update employee commission (reduce)
7. ✅ Increase stock quantity
8. ✅ **Generate Return Bill/Receipt** (NEW!)
9. ✅ Print return receipt
10. ✅ P2P feature untouched

**Cash Return System is now complete and fully functional!** 🎉
