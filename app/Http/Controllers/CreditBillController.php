<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\CreditBill;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class CreditBillController extends Controller
{
    public function index()
    {
        $allCreditBills = CreditBill::with(['customer', 'sale'])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalCreditBills = CreditBill::all();

        $stats = [
            'total_pending' => CreditBill::pending()->sum('remaining_amount'),
            'total_partial' => CreditBill::partial()->sum('remaining_amount'),
            'overdue_count' => CreditBill::overdue()->count(),
            'total_bills' => CreditBill::count(),
        ];

        return Inertia::render('CreditBill/Index', [
            'allCreditBills' => $allCreditBills,
            'totalCreditBills' => $totalCreditBills,
            'stats' => $stats,
        ]);
    }

    public function show($id)
    {
        $creditBill = CreditBill::with(['customer', 'sale.saleItems.product'])
            ->findOrFail($id);

        return Inertia::render('CreditBill/Show', [
            'creditBill' => $creditBill,
        ]);
    }

    public function updatePayment(Request $request, $id)
    {
        $request->validate([
            'payment_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $creditBill = CreditBill::findOrFail($id);
        $paymentAmount = $request->payment_amount;

        if ($paymentAmount > $creditBill->remaining_amount) {
            return back()->withErrors(['payment_amount' => 'Payment amount cannot exceed remaining amount.']);
        }

        DB::transaction(function () use ($creditBill, $paymentAmount, $request) {
            $creditBill->paid_amount += $paymentAmount;
            $creditBill->remaining_amount -= $paymentAmount;

            // Update payment status
            if ($creditBill->remaining_amount <= 0) {
                $creditBill->payment_status = 'paid';
            } elseif ($creditBill->paid_amount > 0) {
                $creditBill->payment_status = 'partial';
            }

            if ($request->notes) {
                $creditBill->notes = $request->notes;
            }

            $creditBill->save();
        });

        return back()->with('success', 'Payment updated successfully!');
    }

    public function markAsPaid($id)
    {
        $creditBill = CreditBill::findOrFail($id);
        
        $creditBill->update([
            'paid_amount' => $creditBill->total_amount,
            'remaining_amount' => 0,
            'payment_status' => 'paid',
        ]);

        return back()->with('success', 'Credit bill marked as paid!');
    }

    public function destroy($id)
    {
        $creditBill = CreditBill::findOrFail($id);
        $creditBill->delete();

        return back()->with('success', 'Credit bill deleted successfully!');
    }
}
