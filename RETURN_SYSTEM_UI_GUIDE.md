# Return System UI Guide

## UI Changes Overview

### 1. Return Bills Modal - Enhanced Table

The return items table now includes:

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│ Product  │ Quantity │ Unit Price │ Total Price │ Return Type │ New Product │ Reason │ Date │ Action │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ [Image]  │  [- 2 +] │   1000.00  │   2000.00   │  [Dropdown] │  [Dropdown] │ [Text] │ Date │   X    │
│ Apple    │          │            │             │  Cash       │    N/A      │        │      │        │
│ Avail: 8 │          │            │             │             │             │        │      │        │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 2. Return Type Dropdown

Each item has a return type selector:

```html
<select>
  <option value="cash">Cash Return</option>
  <option value="p2p">Product-to-Product (P2P)</option>
</select>
```

**Cash Return Selected:**
- New Product column shows "N/A"
- Return amount deducted from bill

**P2P Return Selected:**
- New Product dropdown appears
- New Product amount input appears
- Shows all available products

### 3. New Product Selection (P2P Only)

When P2P is selected:

```
┌──────────────────────────────────┐
│ New Product (P2P)                │
├──────────────────────────────────┤
│ [Select New Product ▼]           │
│  - Orange - 1200 LKR             │
│  - Banana - 800 LKR              │
│  - Mango - 1500 LKR              │
│                                  │
│ Amount: [____________]           │
│         Enter custom amount      │
└──────────────────────────────────┘
```

### 4. Employee Information Display

When an order is selected:

```
┌─────────────────────────────────┐
│ Selected Order Details:         │
├─────────────────────────────────┤
│ Order ID: CH/25.11.24/0023     │
│ Customer Name: John Doe         │
│ Employee: Jane Smith ⭐         │  ← Auto-displayed
│ Total Amount: 10000.00 LKR      │
│ Discount: 500.00 LKR            │
│ Payment Method: Cash            │
│ Sale Date: 2024-11-24           │
└─────────────────────────────────┘
```

### 5. Quantity Controls with Validation

```
┌──────────────────────┐
│ Quantity             │
├──────────────────────┤
│   [+]  2  [-]        │
│                      │
│ Available: 8 units   │  ← Shows remaining quantity
└──────────────────────┘
```

- Plus button increases quantity (up to available)
- Minus button decreases quantity (minimum 1)
- Shows available quantity for returns

### 6. Complete Table Example

```
╔═══════════════════════════════════════════════════════════════════════════════════════════════════════╗
║                              Items in this Sale                                                        ║
╠═══════════╦══════════╦════════════╦═════════════╦═════════════╦═════════════╦════════╦══════╦════════╣
║ Product   ║ Quantity ║ Unit Price ║ Total Price ║ Return Type ║ New Product ║ Reason ║ Date ║ Action ║
╠═══════════╬══════════╬════════════╬═════════════╬═════════════╬═════════════╬════════╬══════╬════════╣
║ [Image]   ║          ║            ║             ║             ║             ║        ║      ║        ║
║ Apple     ║ [- 2 +]  ║  1000.00   ║   2000.00   ║ [Cash ▼]    ║    N/A      ║ [____] ║ Date ║   X    ║
║ Avail: 8  ║          ║            ║             ║             ║             ║        ║      ║        ║
╠═══════════╬══════════╬════════════╬═════════════╬═════════════╬═════════════╬════════╬══════╬════════╣
║ [Image]   ║          ║            ║             ║             ║             ║        ║      ║        ║
║ Orange    ║ [- 1 +]  ║  1200.00   ║   1200.00   ║ [P2P ▼]     ║ [Mango ▼]   ║ [____] ║ Date ║   X    ║
║ Avail: 5  ║          ║            ║             ║             ║ Amt: 1500   ║        ║      ║        ║
╚═══════════╩══════════╩════════════╩═════════════╩═════════════╩═════════════╩════════╩══════╩════════╝
```

## User Workflow

### Scenario 1: Cash Return

1. User clicks "Return Bills" button
2. Modal opens
3. User selects order from dropdown
4. **Employee name automatically appears** ← Key Feature
5. Sale items load with available quantities
6. User configures return:
   ```
   Product: Apple
   Quantity: 2 (Available: 10)
   Return Type: Cash Return ← Default
   Reason: Damaged goods
   Date: 2024-11-24
   ```
7. User clicks "Save"
8. System calculates:
   ```
   Return Amount: 2 × 1000 = 2000 LKR
   New Bill Total: 10000 - 2000 = 8000 LKR
   Employee Commission: Reduced proportionally
   Stock: Apple stock increases by 2
   ```

### Scenario 2: Product-to-Product (P2P) Return

1. User clicks "Return Bills" button
2. Modal opens
3. User selects order from dropdown
4. **Employee name automatically appears**
5. Sale items load
6. User configures return:
   ```
   Product: Apple
   Quantity: 2 (Available: 10)
   Return Type: Product-to-Product (P2P) ← User selects
   New Product: Orange (1200 LKR)
   New Product Amount: 1200 LKR (or custom)
   Reason: Customer wants different product
   Date: 2024-11-24
   ```
7. User clicks "Save"
8. System calculates:
   ```
   Return Amount: 2 × 1000 = 2000 LKR
   New Product Amount: 1200 LKR
   
   Bill Calculation (as per requirement):
   New Total = Old Total - Return Amount + New Product Amount
   New Total = 10000 - 2000 + 1200 = 9200 LKR
   
   Stock Updates:
   - Apple: +2 units (returned)
   - Orange: -1 unit (given to customer)
   
   Commission Updates:
   - Remove Apple commission for returned units
   - Add Orange commission for new product
   ```

## Validation Messages

### Error Messages Display:
```
┌─────────────────────────────────────────────────────┐
│ ⚠ Please provide a reason for all return items     │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│ ⚠ Please select a new product for P2P returns      │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│ ⚠ Cannot return 5 units. Only 3 units available    │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│ ⚠ New product 'Orange' is out of stock             │
└─────────────────────────────────────────────────────┘
```

### Success Message:
```
┌─────────────────────────────────────────────────────┐
│ ✓ Return processed successfully!                    │
└─────────────────────────────────────────────────────┘
```

## Visual Indicators

### Available Quantity Display
```
Product: Apple
[Image]
Available: 8 units  ← Green text if > 0, Red if 0
```

### Return Type Selection
```
Cash Return       ← Shows in black
P2P Return        ← Shows in black
```

### New Product Field
```
When Cash selected:
New Product: N/A  ← Gray text

When P2P selected:
New Product: [Dropdown with products]  ← Blue dropdown
Amount: [Input field]  ← Editable
```

## Button States

### Save Button
```
[     Save     ]  ← Enabled (blue)
[     Save     ]  ← Disabled (gray) when validation fails
```

### Cancel Button
```
[   Cancel    ]  ← Always enabled (gray)
```

## Responsive Design

The modal is:
- Scrollable for long lists
- Max width: 7xl (extra large)
- Padding: 20px
- Overflow: auto (for many items)

## Color Scheme

- Primary actions: Blue (#2563eb)
- Errors: Red (#dc2626)
- Success: Green (#16a34a)
- Borders: Gray (#d1d5db)
- Background: White/Gray-50
- Text: Black/Gray-700

## Accessibility

- Clear labels for all inputs
- Keyboard navigation supported
- Focus states on interactive elements
- Error messages prominently displayed
- Available quantity always visible

## Mobile Considerations

- Touch-friendly +/- buttons
- Dropdown selections easy to tap
- Adequate spacing between elements
- Scrollable content areas
- Responsive table layout

This UI implementation provides a clear, user-friendly interface for both Cash and P2P returns with all required features prominently displayed and easy to use.
