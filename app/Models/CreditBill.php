<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditBill extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'customer_id',
        'order_id',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'payment_status',
        'notes'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    // Relationships
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function sales()
    {
        return $this->hasMany(Sale::class, 'customer_id', 'customer_id')
            ->where('payment_method', 'credit bill');
    }

    public function payments()
    {
        return $this->hasMany(CreditBillPayment::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopePartial($query)
    {
        return $query->where('payment_status', 'partial');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('payment_status', ['pending', 'partial']);
    }

    // Overdue scope removed - due dates not used

    // Methods to handle payment updates
    public function updatePaymentAmounts()
    {
        // Get upfront payment from the linked sale (cash + card)
        $upfrontPayment = 0;
        if ($this->sale) {
            $upfrontPayment = ($this->sale->cash ?? 0) + ($this->sale->card ?? 0);
        }
        
        // Calculate additional payments made via credit bill payments table
        $creditBillPaymentsTotal = $this->payments()->sum('payment_amount');
        
        // Total paid = upfront payment + credit bill payments
        $totalPaid = $upfrontPayment + $creditBillPaymentsTotal;
        
        // Update amounts
        $this->paid_amount = $totalPaid;
        $this->remaining_amount = max(0, $this->total_amount - $totalPaid);
        
        // Update payment status
        if ($this->remaining_amount <= 0) {
            $this->payment_status = 'paid';
        } elseif ($totalPaid > 0) {
            $this->payment_status = 'partial';
        } else {
            $this->payment_status = 'pending';
        }
        
        $this->save();
        
        return $this;
    }

    public function addPayment($amount, $paymentMethod = 'cash', $notes = null, $userId = null)
    {
        // Validate payment amount
        if ($amount <= 0 || $amount > $this->remaining_amount) {
            throw new \Exception('Invalid payment amount');
        }

        // Create payment record
        $payment = $this->payments()->create([
            'payment_amount' => $amount,
            'payment_date' => now(),
            'payment_method' => $paymentMethod,
            'notes' => $notes,
            'user_id' => $userId
        ]);

        // Update amounts (this will be handled by the payment model events)
        return $payment;
    }

    /**
     * Create or update credit bill with proper total and remaining amounts
     * 
     * @param int|null $customerId The customer ID (can be null for walk-in)
     * @param int $saleId The sale ID (from the main sale)
     * @param string $orderId The order ID
     * @param float $fullOrderTotal The FULL order total (not just credit portion)
     * @param float $creditBillAmount The amount still owed via credit bill (remaining to pay)
     * @param float $upfrontPayment The upfront payment made immediately (cash + card)
     * @return CreditBill
     */
    public static function createOrUpdateWithAmount($customerId, $saleId, $orderId, $fullOrderTotal, $creditBillAmount, $upfrontPayment = 0, $notes = null)
    {
        if ($customerId) {
            // Try to find existing unpaid credit bill for customer
            $existingCreditBill = static::where('customer_id', $customerId)
                ->whereIn('payment_status', ['pending', 'partial'])
                ->first();

            if ($existingCreditBill) {
                // Update existing credit bill - add new amounts
                $newTotal = $existingCreditBill->total_amount + $fullOrderTotal;
                $newRemaining = $existingCreditBill->remaining_amount + $creditBillAmount;
                $newPaid = $existingCreditBill->paid_amount + $upfrontPayment;
                
                $existingCreditBill->update([
                    'sale_id' => $saleId,
                    'order_id' => $orderId,
                    'total_amount' => $newTotal,
                    'paid_amount' => $newPaid,  // Add upfront payment to paid_amount
                    'remaining_amount' => $newRemaining,
                    'notes' => ($existingCreditBill->notes ?: '') . 
                              ($existingCreditBill->notes ? '; ' : '') . 
                              ($notes ?: "Updated with sale ID: {$saleId} (Order: {$orderId})")
                ]);
                
                // Update status based on new amounts
                $existingCreditBill->updatePaymentAmounts();
                
                return $existingCreditBill;
            }
        }
        
        // Create new credit bill
        $newBill = static::create([
            'sale_id' => $saleId,
            'customer_id' => $customerId,
            'order_id' => $orderId,
            'total_amount' => $fullOrderTotal,  // FULL order total
            'paid_amount' => $upfrontPayment,   // Set initial paid amount to upfront payment
            'remaining_amount' => $creditBillAmount,  // Amount still owed via credit bill
            'payment_status' => $upfrontPayment > 0 ? 'partial' : 'pending',  // Mark as partial if upfront payment made
            'notes' => $notes ?: ($customerId ? 'Auto-generated from POS sale' : 'Auto-generated from POS sale (no customer)'),
        ]);
        
        return $newBill;
    }

    /**
     * Update existing credit bill for customer or create new one
     * DEPRECATED: Use createOrUpdateWithAmount instead
     */
    public static function updateOrCreateForCustomer($customerId, $saleId, $orderId, $amount, $notes = null)
    {
        if ($customerId) {
            // Try to find existing unpaid credit bill for customer
            $existingCreditBill = static::where('customer_id', $customerId)
                ->whereIn('payment_status', ['pending', 'partial'])
                ->first();

            if ($existingCreditBill) {
                // Update existing credit bill - add the new amount to total
                $newTotal = $existingCreditBill->total_amount + $amount;
                $newRemaining = $existingCreditBill->remaining_amount + $amount;
                
                $existingCreditBill->update([
                    'sale_id' => $saleId, // Always use the latest sale_id
                    'order_id' => $orderId, // Update with latest order ID
                    'total_amount' => $newTotal,
                    'remaining_amount' => $newRemaining,
                    'notes' => ($existingCreditBill->notes ?: '') . 
                              ($existingCreditBill->notes ? '; ' : '') . 
                              ($notes ?: "Updated with sale ID: {$saleId} (Order: {$orderId})")
                ]);
                
                return $existingCreditBill;
            }
        }
        
        // Create new credit bill if no existing one found or no customer
        return static::create([
            'sale_id' => $saleId,
            'customer_id' => $customerId,
            'order_id' => $orderId,
            'total_amount' => $amount,
            'paid_amount' => 0,
            'remaining_amount' => $amount,
            'payment_status' => 'pending',
            'notes' => $notes ?: ($customerId ? 'Auto-generated from POS sale' : 'Auto-generated from POS sale (no customer)'),
        ]);
    }

    /**
     * Consolidate multiple credit bills for the same customer into one
     */
    public static function consolidateCustomerBills($customerId)
    {
        if (!$customerId) return null;
        
        $bills = static::where('customer_id', $customerId)
            ->whereIn('payment_status', ['pending', 'partial'])
            ->orderBy('created_at')
            ->get();
            
        if ($bills->count() <= 1) {
            return $bills->first();
        }
        
        // Keep the oldest bill and merge others into it
        $mainBill = $bills->first();
        $totalAmount = $bills->sum('total_amount');
        $totalRemaining = $bills->sum('remaining_amount');
        $totalPaid = $bills->sum('paid_amount');
        
        // Collect all notes and order IDs
        $allNotes = $bills->pluck('notes')->filter()->implode('; ');
        $allOrderIds = $bills->pluck('order_id')->filter()->unique()->implode(', ');
        
        // Update the main bill
        $mainBill->update([
            'total_amount' => $totalAmount,
            'remaining_amount' => $totalRemaining,
            'paid_amount' => $totalPaid,
            'notes' => "Consolidated bill - Orders: {$allOrderIds}. {$allNotes}"
        ]);
        
        // Delete the other bills (except the main one)
        static::where('customer_id', $customerId)
            ->whereIn('payment_status', ['pending', 'partial'])
            ->where('id', '!=', $mainBill->id)
            ->delete();
            
        \Log::info("Consolidated {$bills->count()} credit bills into bill ID: {$mainBill->id} for customer ID: {$customerId}");
        
        return $mainBill;
    }
}
