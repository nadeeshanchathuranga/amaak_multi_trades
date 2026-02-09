<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\CompanyInfo;
use App\Models\Employee;
use App\Models\Category;
use App\Models\Color;
use App\Models\Size;
use App\Models\Sale;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class ManualPosController extends Controller
{
    /**
     * Generate the next order ID in the format CH/YY.MM.DD/NNNN
     * Thread-safe using database transactions
     */
    private function generateNextOrderId()
    {
        $prefix = "CH";
        $today = now()->format('y.m.d'); // YY.MM.DD format
        $datePattern = $prefix . '/' . $today . '/%';

        return DB::transaction(function () use ($prefix, $today, $datePattern) {
            // Get the latest order_id for today
            $latestSale = Sale::where('order_id', 'like', $datePattern)
                ->orderByRaw("CAST(SUBSTRING_INDEX(order_id, '/', -1) AS UNSIGNED) DESC")
                ->lockForUpdate() // Prevent concurrent access
                ->first();

            $nextNumber = 1;
            if ($latestSale && $latestSale->order_id) {
                // Extract the number part and increment
                $parts = explode('/', $latestSale->order_id);
                if (count($parts) === 3) {
                    $currentNumber = (int) $parts[2];
                    $nextNumber = $currentNumber + 1;
                }
            }

            // Format with leading zeros
            $formattedNumber = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            return $prefix . '/' . $today . '/' . $formattedNumber;
        });
    }

    public function index()
{
    if (!Gate::allows('hasRole', ['Admin', 'Cashier', 'Operator'])) {
        abort(403, 'Unauthorized');
    }
    
    // Generate the next order ID
    $initialOrderId = $this->generateNextOrderId();
    
    $companyInfo = CompanyInfo::first();
    $loggedInUser = auth()->user();
    $allemployee = Employee::orderBy('created_at', 'desc')->get();
    $allcategories = Category::with('parent')->get();
    $colors = Color::orderBy('created_at', 'desc')->get();
    $sizes = Size::orderBy('created_at', 'desc')->get();

    return Inertia::render('ManualPos/Index', [
        'companyInfo' => $companyInfo,
        'loggedInUser' => $loggedInUser,
        'allemployee' => $allemployee,
        'allcategories' => $allcategories,
        'colors' => $colors,
        'sizes' => $sizes,
        'initialOrderId' => $initialOrderId,
    ]);
}
}
