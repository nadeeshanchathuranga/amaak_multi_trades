<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierPayment extends Model
{
    use HasFactory;

      protected $fillable = [
        'supplier_id',
        'supplier_invoice_id',
        'invoice_number',
        'description',
        'total_cost',
        'pay',
        'status', 
    ];


      public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function supplierInvoice()
    {
        return $this->belongsTo(SupplierInvoice::class);
    }

    /**
     * Accessor for balance if you want dynamic calculation
     */
    public function getBalanceAttribute()
    {
        $totalCost = $this->total_cost ?? 0;
        return $totalCost - $this->pay;
    }

    /**
     * Mark payment complete if fully paid
     */
    public function markComplete()
    {
        if ($this->balance <= 0) {
            $this->status = 'complete';
            $this->save();
        }
    }

}
