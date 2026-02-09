<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\SupplierInvoice; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;



class SupplierController extends Controller
{
 


  public function index()
    {
        // Get all suppliers with their invoices and payment summary
        $allsuppliers = Supplier::with('invoices')->orderBy('id', 'desc')->get()->map(function ($supplier) {
            // Calculate payment summary for each supplier from invoices
            $totalOutstanding = $supplier->invoices->sum('remaining_amount');
            $totalInvoiceAmount = $supplier->invoices->sum('total_amount');
            $paidTotal = $supplier->invoices->sum('paid_amount');
            
            // Add payment summary to supplier object
            $supplier->total_outstanding = $totalOutstanding;
            $supplier->total_invoice_amount = $totalInvoiceAmount;
            $supplier->paid_total = $paidTotal;
            $supplier->balance = $totalOutstanding;
            
            return $supplier;
        });

        return Inertia::render('Suppliers/Index', [
            'allsuppliers' => $allsuppliers,
            'totalSuppliers' => $allsuppliers->count()
        ]);
    }


    // public function create()
    // {
    //     $categories = Category::all();

    //     return Inertia::render('Categories/Create', [
    //         'categories' => $categories,
    //     ]);
    // }

    public function store(Request $request)
    {
        if (!Gate::allows('hasRole', ['Admin'])) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:191|regex:/^[a-zA-Z\s]+$/',
           'contact' => 'required|string|regex:/^\d{10}$/',
            'email' => 'required|email|regex:/^[\w\.-]+@[a-zA-Z0-9\.-]+\.[a-zA-Z]{2,6}$/|max:255|unique:suppliers,email',
            'address' => 'required|string|max:500',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:1024',
        ]);



        // if ($request->hasFile('image')) {
        //     $fileExtension = $request->file('image')->getClientOriginalExtension();
        //     $fileName = 'supplier' . date("YmdHis") . '.' . $fileExtension;
        //     $destinationPath = "images/uploads/supplier/";
        //     $request->file('image')->move(public_path($destinationPath), $fileName);
        //     $validated['image'] = $destinationPath . $fileName;
        // }

        if ($request->hasFile('image')) {
            $fileExtension = $request->file('image')->getClientOriginalExtension();
            $fileName = 'supplier_' . date("YmdHis") . '.' . $fileExtension;
            $path = $request->file('image')->storeAs('suppliers', $fileName, 'public');
            $validated['image'] = 'storage/' . $path;
        }

        Supplier::create($validated);

        return redirect()->route('suppliers.index')->banner('Supplier created successfully.');
    }


    public function update(Request $request, Supplier $supplier)
    {

        if (!Gate::allows('hasRole', ['Admin'])) {
            abort(403, 'Unauthorized');
        }
        // Validate incoming data
        $validated = $request->validate([
            'name' => 'nullable|string|max:191',
            'contact' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:suppliers,email,' . $supplier->id,
            'address' => 'nullable|string|max:500',
            'image' => 'nullable|mimes:jpeg,png,jpg,gif|max:2048',
        ]);


        if ($request->hasFile('image')) {
            // Delete the old image if it exists
            if ($supplier->image && Storage::disk('public')->exists(str_replace('storage/', '', $supplier->image))) {
                Storage::disk('public')->delete(str_replace('storage/', '', $supplier->image));
            }

            // Save the new image
            $fileExtension = $request->file('image')->getClientOriginalExtension();
            $fileName = 'supplier_' . date("YmdHis") . '.' . $fileExtension;
            $path = $request->file('image')->storeAs('suppliers', $fileName, 'public');
            $validated['image'] = 'storage/' . $path;
        } else {
            // Retain the old image if no new image is uploaded
            $validated['image'] = $supplier->image;
        }


        $supplier->update($validated);


        // Redirect back with success message
        return redirect()->route('suppliers.index')->banner('Supplier updated successfully.');
    }





    public function destroy(Supplier $supplier)
    {
        if (!Gate::allows('hasRole', ['Admin'])) {
            abort(403, 'Unauthorized');
        }

        if ($supplier->image && Storage::disk('public')->exists(str_replace('storage/', '', $supplier->image))) {
            Storage::disk('public')->delete(str_replace('storage/', '', $supplier->image));
        }

        $supplier->delete();

        return redirect()->route('suppliers.index')->banner('Supplier deleted successfully.');
    }

    public function showProducts($id)
    {
        $supplier = Supplier::findOrFail($id);
        $products = Product::where('supplier_id', $id)->with('category')->get();
        
        // Get stock transfers for all products from this supplier
        $transfers = \DB::table('stock_transactions')
            ->join('products', 'stock_transactions.product_id', '=', 'products.id')
            ->where('products.supplier_id', $id)
            ->select(
                'stock_transactions.*',
                'products.name as product_name'
            )
            ->orderBy('stock_transactions.created_at', 'desc')
            ->get();

        return Inertia::render('Suppliers/Show', [
            'supplier' => $supplier,
            'products' => $products,
            'transfers' => $transfers,
        ]);
    }



    public function supplierPayment(Request $request)
    {
        if (!Gate::allows('hasRole', ['Admin'])) {
            abort(403, 'Unauthorized');
        }
        
        // Basic validation
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'action_type' => 'required|in:create_invoice,make_payment',
        ]);

        // Conditional validation based on action type
        if ($request->action_type === 'create_invoice') {
            $validated = $request->validate([
                'supplier_id' => 'required|exists:suppliers,id',
                'invoice_number' => 'required|string|max:255',
                'total_cost' => 'required|numeric|min:0.01',
                'action_type' => 'required|in:create_invoice,make_payment',
            ]);
        } else {
            $validated = $request->validate([
                'supplier_id' => 'required|exists:suppliers,id',
                'invoice_number' => 'nullable|string|max:255',
                'description' => 'required|string|max:255', // Payment method is required
                'pay' => 'required|numeric|min:0.01',
                'action_type' => 'required|in:create_invoice,make_payment',
                'invoice_id' => 'nullable|exists:supplier_invoices,id',
            ]);
        }

        $supplier = Supplier::findOrFail($validated['supplier_id']);

        if ($validated['action_type'] === 'create_invoice') {
            // Create new invoice
            $invoice = SupplierInvoice::create([
                'supplier_id' => $supplier->id,
                'invoice_number' => $validated['invoice_number'],
                'total_amount' => $validated['total_cost'],
                'status' => 'pending'
            ]);

            return redirect()->route('suppliers.payments', $supplier->id)
                ->with('message', 'Invoice created successfully');
        } else {
            // Make payment against existing invoice or create payment without invoice
            $payment = DB::transaction(function () use ($validated, $supplier) {
                $invoice = null;

                if (!empty($validated['invoice_id'])) {
                    $invoice = SupplierInvoice::where('supplier_id', $supplier->id)
                        ->find($validated['invoice_id']);
                }

                if (!$invoice && !empty($validated['invoice_number'])) {
                    $invoice = SupplierInvoice::where('supplier_id', $supplier->id)
                        ->where('invoice_number', $validated['invoice_number'])
                        ->first();
                }

                $payment = SupplierPayment::create([
                    'supplier_id' => $supplier->id,
                    'supplier_invoice_id' => $invoice?->id,
                    'invoice_number' => $validated['invoice_number'] ?? $invoice?->invoice_number,
                    'description' => $validated['description'] ?? null,
                    'total_cost' => $validated['total_cost'] ?? $invoice?->total_amount,
                    'pay' => $validated['pay'],
                    'status' => 'complete'
                ]);

                // Update invoice if payment is against an invoice
                if ($invoice) {
                    $invoice->paid_amount += $validated['pay'];
                    $invoice->save(); // Save the paid_amount first
                    $invoice->updateStatus(); // Then update status
                }

                return $payment;
            });

            // Calculate updated totals
            $totalOutstanding = SupplierInvoice::where('supplier_id', $supplier->id)
                ->sum('remaining_amount');
            $totalPaid = SupplierPayment::where('supplier_id', $supplier->id)
                ->sum('pay');

            return redirect()->route('suppliers.payments', $supplier->id)
                ->with('message', 'Payment recorded successfully');
        }
    }


    /**
     * Return a quick payment summary for a supplier.
     * GET /suppliers/{id}/summary
     */
    public function paymentSummary($id)
    {
        $supplier = Supplier::with(['invoices', 'payments'])->findOrFail($id);

        // Calculate total outstanding from invoices
        $totalOutstanding = $supplier->invoices->sum('remaining_amount');
        
        // Sum of all payments made
        $paidTotal = $supplier->payments->sum('pay');

        $balance = max(0, $totalOutstanding);
        $status = $balance <= 0 ? 'complete' : 'pending';

        return response()->json([
            'supplier_id' => $supplier->id,
            'total_outstanding' => $totalOutstanding,
            'paid_total' => $paidTotal,
            'balance' => $balance,
            'status' => $status,
            'invoices' => $supplier->invoices->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'description' => $invoice->description,
                    'total_amount' => $invoice->total_amount,
                    'paid_amount' => $invoice->paid_amount,
                    'remaining_amount' => $invoice->remaining_amount,
                    'status' => $invoice->status,
                    'invoice_date' => $invoice->invoice_date->format('Y-m-d')
                ];
            })
        ]);
    }

    /**
     * Download supplier payment history as an HTML (printable) report.
     * GET /suppliers/{id}/payments/pdf
     */
    public function downloadPaymentsPDF($id)
    {
        try {
            // eager-load payments for calculations
            $supplier = Supplier::with(['payments' => function ($q) {
                $q->orderBy('created_at', 'desc');
            }])->findOrFail($id);

            $payments = $supplier->payments;

            // Calculate totals from payment records instead of products
            $totalOutstanding = SupplierPayment::where('supplier_id', $supplier->id)
                ->whereNotNull('total_cost')
                ->sum('total_cost');
            
            $paidTotal = $payments->sum('pay');
            $balance = max(0, $totalOutstanding - $paidTotal);

            // Build HTML report
            $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Supplier Payments</title><style>body{font-family:Arial,Helvetica,sans-serif;margin:20px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:8px}th{background:#f3f4f6}</style></head><body>';
            $html .= '<h2>Supplier Payment History</h2>';
            $html .= '<div><strong>Supplier:</strong> ' . e($supplier->name) . '</div>';
            $html .= '<div><strong>Generated:</strong> ' . now()->format('F d, Y h:i A') . '</div>';
            $html .= '<div style="margin-top:10px"><strong>Total Outstanding:</strong> LKR ' . number_format($totalOutstanding,2) . ' &nbsp;&nbsp; <strong>Paid:</strong> LKR ' . number_format($paidTotal,2) . ' &nbsp;&nbsp; <strong>Balance:</strong> LKR ' . number_format($balance,2) . '</div>';

            $html .= '<table style="margin-top:20px"><thead><tr><th>#</th><th>Date</th><th>Invoice Number</th><th>Description</th><th>Total Cost</th><th>Pay</th><th>Status</th></tr></thead><tbody>';
            if ($payments->count()) {
                $i = 1;
                foreach ($payments as $p) {
                    $html .= '<tr>';
                    $html .= '<td>' . $i++ . '</td>';
                    $html .= '<td>' . $p->created_at->format('M d, Y h:i A') . '</td>';
                    $html .= '<td>' . e($p->invoice_number ?? 'N/A') . '</td>';
                    $html .= '<td>' . e($p->description ?? 'N/A') . '</td>';
                    $html .= '<td>LKR ' . number_format($p->total_cost ?? 0, 2) . '</td>';
                    $html .= '<td>LKR ' . number_format($p->pay,2) . '</td>';
                    $html .= '<td>' . e($p->status) . '</td>';
                    $html .= '</tr>';
                }
            } else {
                $html .= '<tr><td colspan="7" style="text-align:center;padding:20px">No payments recorded.</td></tr>';
            }
            $html .= '</tbody></table>';

            $html .= '<script>window.onload=function(){setTimeout(function(){window.print()},400)}</script>';
            $html .= '</body></html>';

            // return HTML to render directly in the new tab (no attachment header)
            return response($html)
                ->header('Content-Type', 'text/html');

        } catch (\Exception $e) {
            // Log and show a readable error instead of blank page
            \Log::error('downloadPaymentsPDF error: ' . $e->getMessage());
            $errHtml = '<!DOCTYPE html><html><body><h3>Error generating report</h3><div>' . e($e->getMessage()) . '</div></body></html>';
            return response($errHtml, 500)->header('Content-Type', 'text/html');
        }
    }

    /**
     * Get invoices for a specific supplier
     */
    public function getInvoices($supplierId)
    {
        $invoices = SupplierInvoice::with(['payments' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }])
        ->where('supplier_id', $supplierId)
        ->orderBy('created_at', 'desc')
        ->get();

        return response()->json([
            'invoices' => $invoices
        ]);
    }

    /**
     * Show supplier payments page
     */
    public function showPayments($id)
    {
        $supplier = Supplier::findOrFail($id);
        
        // Get invoices with their payments
        $invoices = SupplierInvoice::where('supplier_id', $id)
            ->with('payments')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Get all payments for this supplier
        $payments = SupplierPayment::where('supplier_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Calculate summary
        $totalInvoiceAmount = $invoices->sum('total_amount');
        $totalOutstanding = $invoices->sum('remaining_amount');
        $paidTotal = $payments->sum('pay');
        $balance = $totalOutstanding;
        
        // Calculate payment method totals
        $cashTotal = $payments->where('description', 'Cash')->sum('pay');
        $cardTotal = $payments->where('description', 'Card')->sum('pay');
        $checkTotal = $payments->where('description', 'Check')->sum('pay');
        
        $summary = [
            'total_invoice_amount' => $totalInvoiceAmount,
            'total_outstanding' => $totalOutstanding,
            'paid_total' => $paidTotal,
            'balance' => $balance,
        ];
        
        $paymentTotals = [
            'cash' => $cashTotal,
            'card' => $cardTotal,
            'check' => $checkTotal,
        ];
        
        return Inertia::render('Suppliers/Payments', [
            'supplier' => $supplier,
            'invoices' => $invoices,
            'payments' => $payments,
            'summary' => $summary,
            'paymentTotals' => $paymentTotals,
        ]);
    }

}