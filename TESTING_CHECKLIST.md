# Return System - Testing Checklist

## Pre-Testing Setup

- [ ] Database migrations run successfully
- [ ] No compilation errors in code
- [ ] Server running (Laravel)
- [ ] Frontend compiled (npm run dev/build)

## Test Case 1: Cash Return - Partial Quantity

**Scenario**: Return 2 out of 10 items

**Steps**:
1. Create a sale with 10 units of Product A @ 1000 LKR each
2. Note: Employee X, Total: 10000 LKR
3. Go to POS > Click "Return Bills"
4. Select the order
5. Verify employee name displays: "Employee X"
6. Set quantity to 2 for Product A
7. Keep "Cash Return" selected
8. Enter reason: "Damaged"
9. Set date
10. Click Save

**Expected Results**:
- [ ] Success message displays
- [ ] Product A stock increases by 2
- [ ] Sale total becomes: 8000 LKR (10000 - 2000)
- [ ] Employee commission reduced proportionally
- [ ] Next time opening this bill shows: Available 8 units
- [ ] Stock transaction created (type: Returned, qty: 2)

**Database Checks**:
```sql
-- Check return_item record
SELECT * FROM return_items WHERE sale_id = [sale_id];
-- Should show: qty=2, return_type='cash', total_price=2000

-- Check updated sale total
SELECT total_amount FROM sales WHERE id = [sale_id];
-- Should be: 8000

-- Check stock
SELECT stock_quantity FROM products WHERE id = [product_id];
-- Should have increased by 2

-- Check commission
SELECT commission_amount FROM employee_commissions 
WHERE sale_id = [sale_id] AND product_id = [product_id];
-- Should be reduced
```

---

## Test Case 2: Cash Return - Full Quantity

**Scenario**: Return all 10 items

**Steps**:
1. Use the same sale from Test Case 1 (now has 8 available)
2. Open return modal
3. Set quantity to 8 (all remaining)
4. Keep "Cash Return" selected
5. Enter reason: "Not needed"
6. Click Save

**Expected Results**:
- [ ] Success message displays
- [ ] Product A stock increases by 8 (total +10 from original)
- [ ] Sale total becomes: 0 LKR or minimal
- [ ] Employee commission for this product deleted or zero
- [ ] Next time: Available 0 units (cannot return more)

---

## Test Case 3: P2P Return

**Scenario**: Exchange Product A for Product B

**Steps**:
1. Create a new sale: 5 units of Product A @ 1000 LKR
2. Employee Y, Total: 5000 LKR
3. Go to return modal, select the order
4. Verify employee name: "Employee Y"
5. Set quantity to 2 for Product A
6. **Select "Product-to-Product (P2P)"**
7. **Select Product B from new product dropdown**
8. **Enter amount: 1200 LKR**
9. Enter reason: "Customer wants Product B"
10. Click Save

**Expected Results**:
- [ ] Success message displays
- [ ] Product A stock increases by 2
- [ ] Product B stock decreases by 1
- [ ] Sale total calculation:
  ```
  Original: 5000 LKR
  Return: 2 × 1000 = 2000 LKR
  New Product: 1200 LKR
  New Total: 5000 - 2000 + 1200 = 4200 LKR
  ```
- [ ] Employee commission:
  - Product A commission reduced for 2 units
  - Product B commission added for 1 unit
- [ ] Two stock transactions created:
  - Product A: Returned, qty: 2
  - Product B: Sold, qty: 1

**Database Checks**:
```sql
-- Check return item with P2P
SELECT * FROM return_items WHERE sale_id = [sale_id];
-- Should show: return_type='p2p', new_product_id=[product_b_id], 
-- new_product_amount=1200

-- Check sale total
SELECT total_amount FROM sales WHERE id = [sale_id];
-- Should be: 4200

-- Check commissions
SELECT * FROM employee_commissions WHERE sale_id = [sale_id];
-- Should show adjusted commission for Product A
-- Should show new commission for Product B
```

---

## Test Case 4: Validation - Exceed Available Quantity

**Scenario**: Try to return more than available

**Steps**:
1. Open a sale with 5 units available for return
2. Try to set return quantity to 10
3. Click Save

**Expected Results**:
- [ ] Error message: "Cannot return 10 units. Only 5 units available for return."
- [ ] No data changes
- [ ] Modal remains open

---

## Test Case 5: Validation - Missing Reason

**Scenario**: Submit without reason

**Steps**:
1. Open return modal
2. Select order, set quantity
3. Leave reason empty
4. Click Save

**Expected Results**:
- [ ] Error message: "Please provide a reason for all return items"
- [ ] No data changes
- [ ] Modal remains open

---

## Test Case 6: Validation - P2P Without New Product

**Scenario**: Select P2P but no new product

**Steps**:
1. Open return modal
2. Select order, set quantity
3. Select "P2P" return type
4. Don't select new product
5. Click Save

**Expected Results**:
- [ ] Error message: "Please select a new product for P2P returns"
- [ ] No data changes

---

## Test Case 7: Multiple Returns - Same Product

**Scenario**: Return same product multiple times

**Steps**:
1. Sale with 10 units of Product A
2. First return: 2 units (Available becomes 8)
3. Second return: 3 units (Available becomes 5)
4. Third return: 5 units (Available becomes 0)
5. Try fourth return

**Expected Results**:
- [ ] After each return, available quantity updates correctly
- [ ] All returns saved independently
- [ ] Stock increases cumulatively: +2, +3, +5 = +10 total
- [ ] Fourth return shows: Available 0 units
- [ ] Cannot return when available = 0

---

## Test Case 8: P2P - Out of Stock New Product

**Scenario**: Select a new product that's out of stock

**Steps**:
1. Set Product B stock to 0
2. Try P2P return selecting Product B
3. Click Save

**Expected Results**:
- [ ] Error message: "New product 'Product B' is out of stock."
- [ ] No data changes
- [ ] Modal remains open

---

## Test Case 9: Employee Information Display

**Scenario**: Verify employee auto-display

**Steps**:
1. Create sales with different employees
2. Open return modal
3. Select Sale 1 (Employee A)
4. Verify employee name shows
5. Change to Sale 2 (Employee B)
6. Verify employee name updates

**Expected Results**:
- [ ] Employee name displays automatically
- [ ] Changes when different sale selected
- [ ] Shows "N/A" if no employee assigned

---

## Test Case 10: UI Functionality

**Scenario**: Test all UI elements

**Steps**:
1. Open return modal
2. Test quantity +/- buttons
3. Test return type dropdown
4. Test new product dropdown (when P2P selected)
5. Test date picker
6. Test remove item button
7. Test cancel button

**Expected Results**:
- [ ] + button increases quantity (max at available)
- [ ] - button decreases quantity (min at 1)
- [ ] Return type changes show/hide new product fields
- [ ] New product dropdown shows all products
- [ ] Date picker works
- [ ] Remove button removes item from list
- [ ] Cancel closes modal without saving

---

## Test Case 11: Real-time Calculations

**Scenario**: Verify dynamic calculations

**Steps**:
1. Add item to return list
2. Change quantity with +/- buttons
3. Observe total price updates
4. Add another item
5. Observe totals update

**Expected Results**:
- [ ] Total Price = Quantity × Unit Price (updates instantly)
- [ ] Available quantity displays correctly
- [ ] All calculations accurate

---

## Test Case 12: Commission Calculation Verification

**Scenario**: Detailed commission check

**Setup**:
- Product A: Category with 10% commission, Price: 1000 LKR
- Sale: 5 units, Employee: John

**Test Return 2 units**:

**Expected Commission Calculation**:
```
Original:
- Quantity: 5
- Total: 5000 LKR
- Commission: 5000 × 10% = 500 LKR

After Return (2 units):
- Remaining: 3
- Total: 3000 LKR
- Commission: 3000 × 10% = 300 LKR
```

**Verify**:
- [ ] Commission reduces from 500 to 300
- [ ] Calculation is automatic
- [ ] No manual intervention needed

---

## Test Case 13: P2P Commission Swap

**Scenario**: Verify commission swap in P2P

**Setup**:
- Product A: 10% commission, Price: 1000 LKR
- Product B: 15% commission, Price: 1200 LKR
- Return 1 unit of A for 1 unit of B

**Expected**:
- [ ] Product A commission reduced: 1000 × 10% = 100 LKR removed
- [ ] Product B commission added: 1200 × 15% = 180 LKR added
- [ ] Net commission change: +80 LKR for employee

---

## Test Case 14: Transaction Rollback

**Scenario**: Force an error mid-process

**Steps**:
1. Set up invalid scenario (e.g., delete product while returning)
2. Submit return
3. Verify error occurs

**Expected Results**:
- [ ] Error message displays
- [ ] NO partial data changes
- [ ] All tables remain unchanged
- [ ] Transaction rolled back successfully

---

## Test Case 15: Concurrent Returns

**Scenario**: Multiple users returning same sale

**Steps**:
1. User A opens return for Sale X (10 available)
2. User B opens return for Sale X (10 available)
3. User A returns 5 units, saves
4. User B tries to return 8 units, saves

**Expected Results**:
- [ ] User A's return succeeds (5 units)
- [ ] User B's return fails or shows error
- [ ] Available quantity = 5 after User A
- [ ] User B sees updated available quantity on refresh
- [ ] Data integrity maintained

---

## Performance Tests

- [ ] Return with 1 item: < 1 second
- [ ] Return with 10 items: < 2 seconds
- [ ] Return with 50 items: < 5 seconds
- [ ] Load sale with 100 items: < 3 seconds
- [ ] Concurrent returns: No race conditions

---

## Browser Compatibility

Test on:
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] Mobile Chrome
- [ ] Mobile Safari

---

## Security Tests

- [ ] Cannot submit without authentication
- [ ] Cannot return items from other users' sales (if applicable)
- [ ] SQL injection attempts fail
- [ ] XSS attempts fail
- [ ] CSRF token validation works

---

## Final Checklist

- [ ] All test cases passed
- [ ] No console errors
- [ ] No database errors
- [ ] Documentation accurate
- [ ] Code reviewed
- [ ] Performance acceptable
- [ ] Security validated
- [ ] Ready for production

---

## Bug Report Template

If issues found:

```
Bug ID: #
Title: [Short description]
Severity: [Critical/High/Medium/Low]

Steps to Reproduce:
1. 
2. 
3. 

Expected Behavior:


Actual Behavior:


Screenshots:


Environment:
- Browser:
- OS:
- Database:

Additional Notes:

```

---

## Notes

- Run tests in a **test environment** first
- Keep backups of production database
- Test with realistic data volumes
- Verify all calculations manually for first few tests
- Document any unexpected behaviors
- Re-test after any fixes
