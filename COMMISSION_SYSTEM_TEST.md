# Commission System Test - Complete Flow

## Scenario: Employee Return and Reissue

### Initial Sale
- **Product A:** 10 units @ 1,000 LKR = 10,000 LKR
- **Commission Rate:** 10%
- **Initial Commission:** 10,000 × 10% = 1,000 LKR

**Database:**
```
employee_commissions table:
- employee_id: 1
- sale_id: 123
- sale_item_id: 456
- product_id: Product A
- quantity: 10
- total_product_amount: 10,000
- commission_amount: 1,000
```

---

### Step 1: Employee Returns 6 Units

**What Happens:**
1. ✅ `sale_items.quantity`: 10 → **4**
2. ✅ `sale_items.total_price`: 10,000 → **4,000**
3. ✅ Product stock: +6 units
4. ✅ Commission adjustment:

```php
// OLD CODE (BUGGY):
$totalReturned = 6; // All returns
$remainingQty = 10 - 6 = 4;
// But commission.quantity was already 10, so it recalculates from original

// NEW CODE (FIXED):
$currentReturnQty = 6; // This return only
$newQuantity = 10 - 6 = 4; // Direct subtraction
$newTotalAmount = 1,000 × 4 = 4,000
$newCommissionAmount = 4,000 × 10% = 400
```

**Updated Database:**
```
employee_commissions table:
- employee_id: 1
- sale_id: 123
- sale_item_id: 456
- product_id: Product A
- quantity: 4 ← Updated from 10
- total_product_amount: 4,000 ← Updated from 10,000
- commission_amount: 400 ← Updated from 1,000
```

**Result:**
- ✅ Commission reduced from 1,000 to **400** (60% reduction for 6 returned items)

---

### Step 2: Employee Issues 3 New Products (P2P)

**What Happens:**
1. ✅ New Sale created (RTN-ABC123)
2. ✅ New product stock: -3 units
3. ✅ **NEW commission record created:**

**Example: Product B @ 2,000 LKR, Commission 10%**

```
New Sale:
- Product B: 3 units @ 2,000 = 6,000 LKR

New Commission:
- commission_amount = 6,000 × 10% = 600 LKR
```

**New Database Record:**
```
employee_commissions table (NEW RECORD):
- employee_id: 1
- sale_id: 789 ← Return sale ID (RTN-ABC123)
- sale_item_id: 999
- product_id: Product B
- quantity: 3
- total_product_amount: 6,000
- commission_amount: 600
```

---

### Final Result: Employee's Total Commission

**Query to get employee's total commission:**
```sql
SELECT 
    employee_id,
    SUM(commission_amount) as total_commission,
    SUM(quantity) as total_items
FROM employee_commissions
WHERE employee_id = 1
GROUP BY employee_id;
```

**Result:**
```
Employee Total Commission:
- Record 1 (Original Sale): 400 (4 remaining items from Product A)
- Record 2 (Return Sale): 600 (3 new items from Product B)
────────────────────────────────────────────────────────
TOTAL: 1,000 LKR
```

---

## How Commissions Are Combined

The system **automatically combines** commissions when you query:

### Backend Query Example:
```php
$employeeCommissions = EmployeeCommission::where('employee_id', $employeeId)
    ->get();

$totalCommission = $employeeCommissions->sum('commission_amount');
// Result: 400 + 600 = 1,000 LKR
```

### Report Query Example:
```php
$report = DB::table('employee_commissions')
    ->select(
        'employee_id',
        DB::raw('SUM(commission_amount) as total_commission'),
        DB::raw('SUM(quantity) as total_items'),
        DB::raw('COUNT(*) as total_sales')
    )
    ->where('employee_id', $employeeId)
    ->groupBy('employee_id')
    ->first();

echo "Total Commission: {$report->total_commission}"; // 1,000
echo "Total Items: {$report->total_items}"; // 7 (4 + 3)
echo "Total Sales: {$report->total_sales}"; // 2
```

---

## Return Bill Generation

### For Cash Returns:
- Return items tracked in `return_items` table
- Original sale total reduced
- Stock increased
- Commission reduced
- **No separate return bill created** (cash refund only)

### For P2P Returns:
- Return items tracked in `return_items` table
- New sale created with order ID: **RTN-XXXXXXXX**
- New products tracked in new sale_items
- Both transactions linked in `p2p_return_transactions`
- **Return bill generated and printable** ✅
- Print bill modal appears automatically

---

## Testing the Fix

### Test 1: Commission Reduction
```bash
# Check before return
SELECT * FROM employee_commissions WHERE sale_item_id = X;
# commission_amount: 1000, quantity: 10

# Process return of 6 units
# POST /return-bill with return_items

# Check after return
SELECT * FROM employee_commissions WHERE sale_item_id = X;
# commission_amount: 400, quantity: 4 ✅
```

### Test 2: Commission Addition
```bash
# Check employee total before P2P
SELECT SUM(commission_amount) FROM employee_commissions WHERE employee_id = Y;
# Result: 400

# Process P2P return with 3 new items
# POST /return-bill with return_items + new_products

# Check employee total after P2P
SELECT SUM(commission_amount) FROM employee_commissions WHERE employee_id = Y;
# Result: 1000 (400 + 600) ✅
```

### Test 3: Return Bill Generation
```bash
# Process P2P return
# Response should include:
{
    "return_sale_id": 789,
    "return_order_id": "RTN-ABC12345",
    "return_sale_data": { ... } // Full bill data for printing
}

# Check database
SELECT * FROM sales WHERE order_id LIKE 'RTN-%';
# Should find the return bill record ✅
```

---

## Key Fixes Applied

1. ✅ **Commission Reduction**: Changed from using total returns to current return quantity
2. ✅ **Commission Addition**: Already working - creates new records for new products
3. ✅ **Commission Combination**: Works via SUM() queries - each sale has separate record
4. ✅ **Return Bill**: Generated for P2P returns with print option
5. ✅ **Stock Updates**: Increases on return, decreases on issue
6. ✅ **Sale Items Update**: Quantity and total_price reduced on returns

---

## Expected Behavior Summary

| Action | Commission Effect | Stock Effect | Sale Effect |
|--------|------------------|--------------|-------------|
| **Initial Sale** | +1,000 (10 items) | -10 units | +10,000 |
| **Return 6** | -600 (reduce to 400) | +6 units | -6,000 (now 4,000) |
| **Issue 3 New** | +600 (new record) | -3 units | +6,000 (new bill) |
| **Total** | 1,000 (400+600) | -7 net | 4,000 + 6,000 (separate) |

✅ **All working correctly now!**
