<?php

namespace App\Modules\Billing\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Billing\Infrastructure\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with('payments')->orderBy('created_at', 'desc')->get();
        return response()->json($invoices);
    }
}
