<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DayClosing;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuperAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:superadmin']);
    }

    /**
     * Reset (delete) all sales, sale items, and day closings.
     */
    public function resetSales(Request $request)
    {
        $request->validate([
            'confirm' => 'required|in:RESET',
        ]);

        DB::transaction(function () {
            SaleItem::query()->delete();
            Sale::query()->delete();
            DayClosing::query()->delete();
        });

        return redirect()->route('dashboard')
            ->with('success', 'All sales, sale items, and day closings have been permanently deleted.');
    }
}
