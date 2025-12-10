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
        'due_date',
        'notes'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'due_date' => 'date',
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

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())->whereIn('payment_status', ['pending', 'partial']);
    }
}
