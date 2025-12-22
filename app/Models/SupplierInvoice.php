<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'invoice_number',
        'description',
        'total_amount',
        'paid_amount',
        'status',
        'invoice_date'
    ];

    protected $casts = [
        'invoice_date' => 'datetime',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    protected $appends = ['remaining_amount'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class, 'supplier_invoice_id');
    }

    public function getRemainingAmountAttribute()
    {
        return round($this->total_amount - $this->paid_amount, 2);
    }

    public function updateStatus()
    {
        if ($this->paid_amount >= $this->total_amount) {
            $this->status = 'paid';
        } elseif ($this->paid_amount > 0) {
            $this->status = 'partial';
        } else {
            $this->status = 'pending';
        }
        $this->save();
    }
}
