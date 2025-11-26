# P2P Return System - Quick Reference Guide

## 🔴 Cash Return Process

### Steps:
1. Click **"Return Bills"**
2. Select **Order Code**
3. Click **"Add to Return"** for product
4. Select **"💵 Cash Return"**
5. Enter **quantity**
6. Enter **reason**
7. Click **"Confirm Order"**

### What Happens:
- ✅ Product stock increases by returned quantity
- ✅ Original bill amount reduced
- ✅ Employee commission adjusted
- ✅ Customer receives cash refund
- ✅ NO separate bill created

### Example:
```
Original: 10 units @ 1,000 LKR = 10,000 LKR
Return: 6 units
Result: 
  - Original bill: 10,000 → 4,000 LKR
  - Stock: +6 units
  - Refund: 6,000 LKR to customer
```

---

## 🔄 P2P Return Process (Product-to-Product)

### Steps:
1. Click **"Return Bills"**
2. Select **Order Code**
3. Click **"Add to Return"** for product
4. Select **"🔄 Product-to-Product (P2P)"**
5. Enter **quantity** to return
6. Enter **reason**
7. Click **"User Manual"** button ← IMPORTANT!
8. Add **new product(s)** customer will receive
9. Click **"Confirm Order"**

### What Happens:
- ✅ Returned product stock increases
- ✅ New product stock decreases
- ✅ Original bill reduced by return amount
- ✅ **NEW SEPARATE BILL CREATED** (RTN-XXXXXXXX)
- ✅ New bill contains the exchanged products
- ✅ Both commissions updated
- ✅ Complete tracking in database

### Example 1: Equal Exchange
```
Return: 6 units @ 1,000 LKR = 6,000 LKR
Receive: 6 units @ 1,000 LKR = 6,000 LKR

Result:
  - Original bill (ORD-12345): 10,000 → 4,000 LKR
  - NEW Return bill (RTN-ABC123): 6,000 LKR
  - Net: 0 (equal exchange)
  - Customer pays: 0
```

### Example 2: Customer Pays More
```
Return: 5 units @ 1,000 LKR = 5,000 LKR
Receive: 4 units @ 1,500 LKR = 6,000 LKR

Result:
  - Original bill: 5,000 → 0 LKR
  - NEW Return bill: 6,000 LKR
  - Net: +1,000 LKR
  - Customer pays: 1,000 LKR extra
```

### Example 3: Customer Gets Refund
```
Return: 8 units @ 1,000 LKR = 8,000 LKR
Receive: 5 units @ 1,000 LKR = 5,000 LKR

Result:
  - Original bill: 8,000 → 0 LKR
  - NEW Return bill: 5,000 LKR
  - Net: -3,000 LKR
  - Customer receives: 3,000 LKR refund
```

---

## 📊 Key Differences: Cash vs P2P

| Feature | Cash Return | P2P Return |
|---------|-------------|------------|
| **New Bill Created?** | ❌ No | ✅ Yes (RTN-XXXXXXXX) |
| **Original Bill** | Reduced | Reduced |
| **Stock Updates** | +Return qty | +Return, -New |
| **Commission** | Reduced | Reduced + New |
| **Customer Payment** | Gets refund | Depends on value difference |
| **Add New Product?** | ❌ No | ✅ Yes (via User Manual) |

---

## 🎯 Success Messages

### Cash Return:
```
Cash Return Processed Successfully!

Returned Amount: 6,000.00 LKR
Original Sale Total Updated.
```

### P2P Return:
```
P2P Return Bill Created Successfully!

Return Bill ID: RTN-ABC12345
Returned Amount: 6,000.00 LKR
New Product Amount: 6,000.00 LKR
Net Amount: 0.00 LKR

Original Sale Updated. New Return Bill Created.
```

---

## ⚠️ Important Rules

### For ALL Returns:
1. ✅ Must select Return Type (Cash or P2P)
2. ✅ Must enter reason
3. ✅ Quantity cannot exceed remaining available
4. ✅ Return date auto-filled (can change)

### For P2P Returns ONLY:
1. ✅ **MUST add new product via "User Manual" button**
2. ✅ System will reject if no new product added
3. ✅ New product can be any quantity/product
4. ✅ Separate bill will be created automatically

---

## 📝 Database Updates

### Cash Return Updates:
```
Tables Updated:
  ✅ sales (original) - amount reduced
  ✅ return_items - return recorded
  ✅ products - stock increased
  ✅ stock_transactions - "Returned" logged
  ✅ employee_commissions - adjusted
  ✅ p2p_return_transactions - tracked
```

### P2P Return Updates:
```
Tables Updated:
  ✅ sales (original) - amount reduced
  ✅ sales (NEW) - return bill created ← NEW ROW
  ✅ sale_items (NEW) - new products
  ✅ return_items - return recorded
  ✅ products - both updated (+return, -new)
  ✅ stock_transactions - both logged
  ✅ employee_commissions - both adjusted
  ✅ p2p_return_transactions - fully tracked with return_sale_id
```

---

## 🔍 How to Find Return Bills

### In Database:
```sql
-- All return bills
SELECT * FROM sales 
WHERE order_id LIKE 'RTN-%';

-- P2P transactions
SELECT * FROM p2p_return_transactions
WHERE transaction_type = 'p2p'
AND return_sale_id IS NOT NULL;
```

### Return Bill ID Format:
- Regular sales: `ORD-XXXXXXXX`
- Return bills: `RTN-XXXXXXXX` ← Separate bills for P2P

---

## 💡 Tips for Cashiers

### Cash Returns:
1. Verify product condition
2. Check original sale details
3. Confirm return quantity
4. Process return
5. Give cash refund to customer

### P2P Returns:
1. Verify returned product
2. **Don't forget to click "User Manual"**
3. Add new product(s) carefully
4. Check quantities
5. Verify total amounts
6. Process exchange
7. Collect payment if customer owes money
8. Give refund if customer gets money back
9. **Give customer BOTH receipts:**
   - Original sale (updated)
   - Return bill (new)

---

## ❓ Troubleshooting

### "For P2P returns, please add the new product(s)"
**Solution:** Click "User Manual" button and add products

### "Return quantity cannot exceed remaining quantity"
**Solution:** Check how much was already returned

### "Insufficient stock for product"
**Solution:** New product doesn't have enough stock, choose different product

### Can't find return bill
**Solution:** Look for order ID starting with "RTN-"

---

## 📊 Quantity Tracking Example

### Original Sale:
```
Product: iPhone 14
Quantity: 10 units
```

### After Return 1 (6 units):
```
Original Sale: 4 units remaining
Available for return: 4 units
```

### After Return 2 (3 units):
```
Original Sale: 1 unit remaining
Available for return: 1 unit
```

### After Return 3 (1 unit):
```
Original Sale: 0 units remaining
Available for return: 0 units ← Cannot return anymore
```

---

## 🎓 Training Scenarios

### Scenario 1: Simple Cash Return
- Customer bought 10 items, returns 6
- Select cash return
- Process → Customer gets 6,000 LKR back

### Scenario 2: Equal P2P Exchange
- Return 6 units @ 1,000 LKR
- Exchange for 6 units @ 1,000 LKR
- Process → No payment, no refund

### Scenario 3: P2P - Customer Pays More
- Return 5 units @ 1,000 LKR = 5,000 LKR
- Exchange for 4 units @ 1,500 LKR = 6,000 LKR
- Process → Customer pays 1,000 LKR extra

### Scenario 4: P2P - Customer Gets Refund
- Return 8 units @ 1,000 LKR = 8,000 LKR
- Exchange for 5 units @ 1,000 LKR = 5,000 LKR
- Process → Customer gets 3,000 LKR back

---

## ✅ Pre-Confirmation Checklist

### Before Clicking Confirm:
- [ ] Correct order code selected
- [ ] Right product added to return
- [ ] Return Type selected (Cash or P2P)
- [ ] Quantity is correct
- [ ] Reason entered
- [ ] Date verified
- [ ] **For P2P: New product(s) added**
- [ ] Amounts look correct

---

## 📞 Need Help?

- Check return type selected properly
- Verify new products added for P2P
- Review success message details
- Contact supervisor for complex cases
- Check documentation for examples

---

**Quick Reminder:**

**Cash Return** = Customer gets money back, no separate bill  
**P2P Return** = Customer exchanges for new product, SEPARATE BILL CREATED (RTN-XXXXXXXX)

---

**Version:** 2.0  
**Updated:** November 25, 2025  
**System:** AMAAK Multi Trades POS with Separate P2P Bills
