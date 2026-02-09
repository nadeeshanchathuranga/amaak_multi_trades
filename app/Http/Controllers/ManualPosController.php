<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\CompanyInfo;
use App\Models\Employee;
use Illuminate\Support\Facades\Gate;

class ManualPosController extends Controller
{
    public function index()
{
    if (!Gate::allows('hasRole', ['Admin', 'Cashier', 'Operator'])) {
        abort(403, 'Unauthorized');
    }
    
    $companyInfo = CompanyInfo::first();
    $loggedInUser = auth()->user();
    $allemployee = Employee::orderBy('created_at', 'desc')->get();

    return Inertia::render('ManualPos/Index', [
        'companyInfo' => $companyInfo,
        'loggedInUser' => $loggedInUser,
        'allemployee' => $allemployee,
    ]);
}
}
