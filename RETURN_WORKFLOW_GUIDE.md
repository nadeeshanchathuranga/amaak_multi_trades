# Return System - New Workflow Guide

## Overview
The return system has been updated so that return type selection happens in the **main Billing Details section**, not in a separate modal. This provides a seamless experience similar to adding regular products.

## Updated User Flow

### Step 1: Open Return Bills Dialog
1. Click the **"Return Bills"** button in the POS Billing Details section
2. A modal opens to select which order to return from

### Step 2: Select Order
1. Choose an order from the dropdown
2. System automatically displays:
   - Order details (Order ID, Customer, Employee, Total, etc.)
   - List of items in that sale with available quantities

### Step 3: Add Items to Return
1. Browse the list of items in the selected sale
2. Each item shows:
   - Product name and image
   - Maximum available quantity for return
   - Unit price
3. Click **"Add to Return"** for items you want to return
4. Item is added to the **Billing Details section** (right side)

### Step 4: Close the Modal
1. After adding all items you want to return, click **"Done"**
2. Modal closes
3. Return items now appear in the **Billing Details section**

### Step 5: Configure Return Details (in Billing Details)
Return items appear with a **red background** and "RETURN" label. For each return item:

1. **Return Type Selection** (dropdown):
   - **Cash Return** (default)
   - **Product-to-Product (P2P)**

2. **If Cash Return selected**:
   - Set quantity (use +/- buttons or type)
   - Enter reason for return (required)
   - Set return date
   - Amount is automatically deducted from total

3. **If P2P selected**:
   - Blue box appears for "New Product (Exchange)"
   - Select new product from dropdown
   - Enter/edit amount for new product
   - Set quantity for return
   - Enter reason (required)
   - Set return date
   - System calculates: Return Amount - New Product Amount

### Step 6: Review Totals
The billing section shows:
- Sub Total (for new products)
- Discount
- **Return Amount** (in red, if returns exist)
- Custom Discount
- Cash
- **Total** (adjusted for returns)

### Step 7: Confirm Order
1. Review all details
2. Click **"CONFIRM ORDER"** at the bottom
3. System processes:
   - Returns with selected type
   - Stock updates
   - Commission adjustments
   - Total calculation

## Visual Layout

```
┌─────────────────────────────────────────────────────────┐
│                    Billing Details                       │
│  [Return Bills]                    [User Manual]        │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  ┌──────────────────────────────────────────────────┐  │
│  │ 🔴 RETURN ITEM (Red Background)                   │  │
│  │ ┌────────┐  RETURN: Apple                        │  │
│  │ │ Image  │  Return Type: [Cash Return ▼]         │  │
│  │ └────────┘                                        │  │
│  │            [- 2 +]  @ 1000 LKR                    │  │
│  │            -2000.00 LKR                           │  │
│  │            Reason: [____________] Date: [____]    │  │
│  │                                                  X │  │
│  └──────────────────────────────────────────────────┘  │
│                                                          │
│  ┌──────────────────────────────────────────────────┐  │
│  │ 🔵 RETURN ITEM - P2P (Red Background)            │  │
│  │ ┌────────┐  RETURN: Orange                       │  │
│  │ │ Image  │  Return Type: [P2P ▼]                 │  │
│  │ └────────┘                                        │  │
│  │            ┌─────────────────────────────────┐   │  │
│  │            │ New Product: [Mango ▼]          │   │  │
│  │            │ Amount: [1500]                  │   │  │
│  │            └─────────────────────────────────┘   │  │
│  │            [- 1 +]  @ 1200 LKR                    │  │
│  │            -1200.00 LKR                           │  │
│  │            Reason: [____________] Date: [____]    │  │
│  │                                                  X │  │
│  └──────────────────────────────────────────────────┘  │
│                                                          │
│  ┌──────────────────────────────────────────────────┐  │
│  │ REGULAR PRODUCT                                   │  │
│  │ ┌────────┐  Banana                               │  │
│  │ │ Image  │  Price: 500 LKR                       │  │
│  │ └────────┘  [- 3 +]                              │  │
│  └──────────────────────────────────────────────────┘  │
│                                                          │
├─────────────────────────────────────────────────────────┤
│  Sub Total              5000.00 LKR                     │
│  Discount               (200.00 LKR)                    │
│  🔴 Return Amount       (2000.00 LKR)                   │
│  Custom Discount        0.00                            │
│  Cash                   3000.00 LKR                     │
│  ─────────────────────────────────────                  │
│  Total                  2800.00 LKR                     │
│  Balance                200.00 LKR                      │
├─────────────────────────────────────────────────────────┤
│  Payment Method: [Cash] [Card] [Koko]                  │
│                                                          │
│        [       CONFIRM ORDER       ]                    │
└─────────────────────────────────────────────────────────┘
```

## Key Features

### Visual Distinction
- **Return items**: Red background, "RETURN" label
- **Regular products**: Normal styling
- **Return amount**: Red text in totals section

### Return Type Selection
Located directly on each return item in the billing area:
```html
Return Type: [Cash Return ▼]  or  [Product-to-Product (P2P) ▼]
```

### P2P New Product Section
Only appears when P2P is selected:
```
┌─────────────────────────────────────┐
│ New Product (Exchange):             │
│ Select Product: [Mango ▼]           │
│ Amount: [1500]                      │
└─────────────────────────────────────┘
```

### Automatic Calculations

**Cash Return:**
```
Return Amount = Quantity × Unit Price
New Total = Original Total - Return Amount
```

**P2P Return:**
```
Return Amount = Quantity × Unit Price
Adjustment = Return Amount - New Product Amount
New Total = Original Total - Adjustment
```

Example:
- Return 2 units of Apple @ 1000 = 2000 LKR
- Exchange for Mango @ 1500 LKR
- Net deduction: 2000 - 1500 = 500 LKR

## Validation

System validates before confirming order:

1. ✅ All return items must have a reason
2. ✅ P2P returns must have new product selected
3. ✅ Return quantity cannot exceed available quantity
4. ✅ New product must be in stock (for P2P)

## Backend Processing

When order is confirmed:

### For Cash Returns:
1. Create return_item record (return_type = 'cash')
2. Update product stock (+returned quantity)
3. Adjust employee commission (reduce proportionally)
4. Update sale total (-return amount)
5. Create stock transaction

### For P2P Returns:
1. Create return_item record (return_type = 'p2p', includes new_product_id)
2. Update returned product stock (+returned quantity)
3. Update new product stock (-1)
4. Remove/adjust commission for returned product
5. Add commission for new product
6. Update sale total (Original - Return + New Product)
7. Create stock transactions for both products

## Advantages of New Approach

1. **Consistency**: Return type selection in same place as product configuration
2. **Clarity**: Clear visual distinction between returns and regular products
3. **Flexibility**: Can mix returns and new products in same transaction
4. **Transparency**: All calculations visible in real-time
5. **Simplicity**: One confirmation button for entire transaction

## Examples

### Example 1: Pure Cash Return
1. Open Return Bills → Select order
2. Add Apple (2 units) → Done
3. In Billing Details:
   - Keep "Cash Return" selected
   - Set quantity: 2
   - Enter reason: "Damaged"
   - Set date
4. Review total (reduced by 2000 LKR)
5. Confirm Order

### Example 2: Pure P2P Return
1. Open Return Bills → Select order
2. Add Orange (1 unit) → Done
3. In Billing Details:
   - Select "Product-to-Product (P2P)"
   - Select new product: Mango
   - Amount auto-fills to 1500
   - Set quantity: 1
   - Enter reason: "Customer wants different product"
   - Set date
4. Review total (reduced by 1200-1500 = -300, so actually increased by 300)
5. Confirm Order

### Example 3: Mixed Transaction (Returns + New Products)
1. Open Return Bills → Select order
2. Add Apple for return → Done
3. In Billing Details:
   - Configure Apple return as Cash Return
   - Add new products using barcode scanner
   - Configure quantities, discounts as normal
4. Review total (includes both returns and new sales)
5. Confirm Order

## Testing Checklist

- [ ] Return items appear in Billing Details with red background
- [ ] Return Type dropdown shows on each return item
- [ ] Switching return type shows/hides P2P section
- [ ] P2P new product selection works
- [ ] Quantity +/- buttons respect max quantity
- [ ] Reason and date fields work
- [ ] Remove button removes return item
- [ ] Return Amount shows in totals (red text)
- [ ] Total calculates correctly with returns
- [ ] Confirm Order processes returns correctly
- [ ] Commission adjustments work
- [ ] Stock updates correctly

## Notes

- Return items can be removed before confirming (X button)
- Can add multiple items from same order
- Can add items from different orders (if needed)
- Return type can be changed at any time before confirmation
- All changes are instant and reflected in totals
