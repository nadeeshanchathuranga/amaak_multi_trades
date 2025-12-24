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
        // Calculate total paid from all payments
        $totalPaid = $this->payments()->sum('payment_amount');
        
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
     * Update existing credit bill for customer or create new one
     */
    public static function updateOrCreateForCustomer($customerId, $saleId, $orderId, $amount, $notes = null)
    {
        if ($customerId) {
            // Try to find existing unpaid credit bill for customer
            $existingCreditBill = static::where('customer_id', $customerId)
                ->whereIn('payment_status', ['pending', 'partial'])
                ->first();

            if ($existingCreditBill) {
                // Update existing credit bill
                $existingCreditBill->update([
                    'total_amount' => $existingCreditBill->total_amount + $amount,
                    'remaining_amount' => $existingCreditBill->remaining_amount + $amount,
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
            'due_date' => now()->addDays(30),
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
