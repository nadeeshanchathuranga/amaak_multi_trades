# Return System - Quick User Guide

## 🔴 Cash Return - Step by Step

### When to Use:
Customer wants money back for returned product

### Steps:
1. Click **"Return Bills"** button (top right of Billing Details)
2. Select the **Order Code** from dropdown
3. Find the product in the list
4. Click **"Add to Return"** button
5. In Billing Details, the item appears with **red background**
6. Select **"💵 Cash Return"** from Return Type dropdown
7. Enter **quantity** to return (max shown)
8. Enter **reason** (required)
9. Verify **return date**
10. Click **"Confirm Order"**

### What Happens:
- ✅ Product stock increases by returned quantity
- ✅ Original bill amount reduced
- ✅ Employee commission adjusted
- ✅ Customer receives cash refund
- ✅ Return recorded in system

---

## 🔄 Product-to-Product (P2P) Return - Step by Step

### When to Use:
Customer wants to exchange returned product for a different product

### Steps:
1. Click **"Return Bills"** button
2. Select the **Order Code** from dropdown
3. Find the product to return
4. Click **"Add to Return"** button
5. In Billing Details, select **"🔄 Product-to-Product (P2P)"** from Return Type dropdown
6. Enter **quantity** to return
7. Enter **reason** (required)
8. Verify **return date**
9. **Click "User Manual"** button (top right)
10. **Add the NEW product** customer will receive
11. Set quantity and details for new product
12. Click **"Confirm Order"**

### What Happens:
- ✅ Returned product stock increases
- ✅ New product stock decreases
- ✅ Original bill adjusted: -returned amount +new product amount
- ✅ Employee commission adjusted for both products
- ✅ P2P exchange recorded

### Example:
**Customer returns:** 6 units @ 1000 LKR = 6000 LKR  
**Customer receives:** 4 units @ 1500 LKR = 6000 LKR  
**Net:** 0 (equal exchange)

---

## ⚠️ Important Notes

### Quantity Rules:
- You can only return up to the **remaining quantity**
- System shows "Max: X" to indicate available quantity
- Multiple partial returns are supported

### Example:
- Original sale: 10 units
- Return 1: 6 units → Remaining: 4 units
- Return 2: 4 units → Remaining: 0 units ✅

### Required Fields:
- ✅ Return Type (Cash or P2P) - **MUST SELECT**
- ✅ Quantity - Must be 1 or more
- ✅ Reason - Cannot be empty
- ✅ Return Date - Auto-filled, can change

### P2P Special Rule:
- **Must add new product via "User Manual" button**
- System will show error if you forget
- New product can be any product in inventory

---

## 💡 Tips & Best Practices

### For Cashiers:
1. **Always verify** the product before accepting return
2. **Check remaining quantity** before entering return amount
3. **Select correct return type** (Cash vs P2P)
4. **Enter clear reason** for returns (helps with reports)
5. **For P2P:** Make sure to add new product before confirming

### Common Mistakes to Avoid:
- ❌ Not selecting return type
- ❌ Forgetting to add new product for P2P
- ❌ Entering quantity more than available
- ❌ Leaving reason field empty
- ❌ Selecting wrong order code

### Visual Indicators:
- **Red background** = Return item
- **Green badge** = Cash Return
- **Blue badge** = P2P Return
- **Blue instruction box** = Reminder to add new product

---

## 🧮 Automatic Calculations

### System Automatically:
- ✅ Calculates return amount (quantity × price)
- ✅ Updates stock quantities
- ✅ Adjusts bill totals
- ✅ Recalculates employee commissions
- ✅ Tracks remaining quantities

### You Don't Need To:
- ❌ Calculate amounts manually
- ❌ Update stock manually
- ❌ Adjust commissions manually
- ❌ Enter prices (uses original price)

---

## 📊 Understanding the Bill Display

```
┌─────────────────────────────────┐
│ Sub Total:        6000.00 LKR   │
│ Discount:         (0.00 LKR)    │
│ Return Amount:    (6000.00 LKR) │ ← Deducted
│ Custom Discount:   0.00         │
│ Cash:             0.00 LKR      │
├─────────────────────────────────┤
│ Total:            0.00 LKR      │ ← Final amount
│ Balance:          0.00 LKR      │
└─────────────────────────────────┘
```

**For P2P:**
- Return Amount is deducted (negative)
- New product amount is added (in Sub Total)
- Total shows net amount customer pays/receives

---

## ❓ Troubleshooting

### "Return quantity cannot exceed remaining quantity"
**Solution:** Check max available quantity. You may have already returned some units.

### "Please select Return Type for all return items"
**Solution:** Click the Return Type dropdown and select Cash or P2P.

### "For P2P returns, please add the new product"
**Solution:** Click "User Manual" button and add the exchange product.

### "Please provide a reason for all return items"
**Solution:** Fill in the Reason field (required).

### Return item disappeared after clicking Confirm
**Solution:** Normal behavior. Return was processed successfully.

---

## 📞 Need Help?

Contact your supervisor or system administrator for:
- Complex return scenarios
- System errors
- Training on new features
- Report generation

---

## 🎯 Quick Checklist

### Before Confirming Return:
- [ ] Correct order code selected
- [ ] Right product added to return
- [ ] Return Type selected (Cash or P2P)
- [ ] Quantity is correct
- [ ] Reason is entered
- [ ] Date is correct
- [ ] For P2P: New product added via User Manual

### After Confirming:
- [ ] Success message appears
- [ ] Return items cleared from screen
- [ ] Customer informed of process
- [ ] Receipt/documentation provided if needed

---

**Version:** 1.0  
**Last Updated:** November 25, 2025  
**System:** AMAAK Multi Trades POS
