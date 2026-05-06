<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\DayClosing;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DayClosingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * List all day closings — owner sees all, cashier sees theirs.
     */
    public function index(Request $request)
    {
        $user   = auth()->user();
        $branch = $request->get('branch_id');
        $month  = $request->get('month', today()->format('Y-m'));

        $query = DayClosing::with('branch', 'closedBy')
            ->whereYear('closing_date',  substr($month, 0, 4))
            ->whereMonth('closing_date', substr($month, 5, 2));

        if ($user->isCashier()) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($branch) {
            $query->where('branch_id', $branch);
        }

        $closings  = $query->latest('closing_date')->get();
        $branches  = $user->isOwner() ? Branch::where('is_active', true)->get() : collect();

        return view('day-closing.index', compact('closings', 'branches', 'branch', 'month'));
    }

    /**
     * Show today's close form for a branch.
     */
    public function close(Request $request)
    {
        $user     = auth()->user();
        $branchId = $user->isCashier() ? $user->branch_id : $request->get('branch_id');

        if (!$branchId) {
            return redirect()->route('day-closing.index')
                ->with('error', 'Please select a branch to close.');
        }

        $branch = Branch::findOrFail($branchId);

        if (DayClosing::where('branch_id', $branchId)->whereDate('closing_date', today())->exists()) {
            return redirect()->route('day-closing.index')
                ->with('info', "Today ({$branch->name}) is already closed.");
        }

        // Calculate today's totals
        $todaySales = Sale::where('branch_id', $branchId)
            ->whereDate('sale_date', today())
            ->whereNull('day_closing_id')
            ->get();

        $summary = [
            'total_sales'          => $todaySales->sum('total'),
            'total_cash_sales'     => $todaySales->where('payment_method', 'cash')->sum('total'),
            'total_transfer_sales' => $todaySales->where('payment_method', 'transfer')->sum('total'),
            'total_card_sales'     => $todaySales->where('payment_method', 'card')->sum('total'),
            'transaction_count'    => $todaySales->count(),
        ];

        $branches = $user->isOwner() ? Branch::where('is_active', true)->get() : collect();

        return view('day-closing.close', compact('branch', 'summary', 'branches'));
    }

    /**
     * Save the day closing record.
     */
    public function store(Request $request)
    {
        $user     = auth()->user();
        $branchId = $user->isCashier() ? $user->branch_id : $request->input('branch_id');

        $request->validate([
            'branch_id'    => 'required|exists:branches,id',
            'opening_cash' => 'required|numeric|min:0',
            'cash_counted' => 'required|numeric|min:0',
            'notes'        => 'nullable|string|max:500',
        ]);

        if (DayClosing::where('branch_id', $branchId)->whereDate('closing_date', today())->exists()) {
            return redirect()->route('day-closing.index')
                ->with('error', 'Day already closed for this branch.');
        }

        DB::transaction(function () use ($request, $branchId, $user) {
            $todaySales = Sale::where('branch_id', $branchId)
                ->whereDate('sale_date', today())
                ->whereNull('day_closing_id')
                ->get();

            $totalSales         = $todaySales->sum('total');
            $totalCash          = $todaySales->where('payment_method', 'cash')->sum('total');
            $totalTransfer      = $todaySales->where('payment_method', 'transfer')->sum('total');
            $totalCard          = $todaySales->where('payment_method', 'card')->sum('total');
            $openingCash        = (float) $request->opening_cash;
            $cashCounted        = (float) $request->cash_counted;
            $expectedCash       = $openingCash + $totalCash;
            $variance           = $cashCounted - $expectedCash;

            $closing = DayClosing::create([
                'branch_id'             => $branchId,
                'user_id'               => $user->id,
                'closing_date'          => today(),
                'opening_cash'          => $openingCash,
                'total_sales'           => $totalSales,
                'total_cash_sales'      => $totalCash,
                'total_transfer_sales'  => $totalTransfer,
                'total_card_sales'      => $totalCard,
                'transaction_count'     => $todaySales->count(),
                'cash_counted'          => $cashCounted,
                'cash_variance'         => $variance,
                'notes'                 => $request->notes,
            ]);

            // Link all today's sales to this closing
            Sale::where('branch_id', $branchId)
                ->whereDate('sale_date', today())
                ->whereNull('day_closing_id')
                ->update(['day_closing_id' => $closing->id]);
        });

        return redirect()->route('day-closing.index')
            ->with('success', 'Day closed successfully!');
    }

    /**
     * View a specific day closing report.
     */
    public function show(DayClosing $dayClosing)
    {
        $user = auth()->user();
        if ($user->isCashier() && $dayClosing->branch_id !== $user->branch_id) {
            abort(403);
        }
        $dayClosing->load('branch.businessType', 'closedBy', 'sales.items');
        return view('day-closing.show', compact('dayClosing'));
    }
}
