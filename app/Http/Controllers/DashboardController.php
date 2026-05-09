<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\DayClosing;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user   = auth()->user();
        $date   = $request->get('date', today()->toDateString());
        $branch = $request->get('branch_id');
        $cashierId = $request->get('cashier_id');

        // Owner sees all branches; cashier sees only their branch
        $query = Sale::with('branch')->whereDate('sale_date', $date);

        if ($user->isCashier()) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($branch) {
            $query->where('branch_id', $branch);
        }

        if ($cashierId) {
            $query->where('user_id', $cashierId);
        }

        $totalSales       = (clone $query)->sum('total');
        $transactionCount = (clone $query)->count();
        $cashSales        = (clone $query)->where('payment_method', 'cash')->sum('total');
        $momoSales        = (clone $query)->where('payment_method', 'mtn_momo')->sum('total');

        // Top 5 items today
        $topItems = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereDate('sales.sale_date', $date)
            ->when($user->isCashier(), fn ($q) => $q->where('sales.branch_id', $user->branch_id))
            ->when($branch && $user->isOwner(), fn ($q) => $q->where('sales.branch_id', $branch))
            ->when($cashierId, fn ($q) => $q->where('sales.user_id', $cashierId))
            ->selectRaw('item_name, SUM(sale_items.quantity) as total_qty, SUM(sale_items.subtotal) as total_amount')
            ->groupBy('item_name')
            ->orderByDesc('total_amount')
            ->limit(5)
            ->get();

        // Sales by branch for owner
        $salesByBranch = [];
        if ($user->isOwner()) {
            $salesByBranch = Sale::whereDate('sale_date', $date)
                ->when($branch, fn ($q) => $q->where('branch_id', $branch))
                ->when($cashierId, fn ($q) => $q->where('user_id', $cashierId))
                ->selectRaw('branch_id, SUM(total) as total_sales, COUNT(*) as txn_count')
                ->groupBy('branch_id')
                ->with('branch')
                ->get();
        }

        // Staff performance by branch (services only)
        $staffPerformanceByBranch = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('branch_staff', 'sale_items.branch_staff_id', '=', 'branch_staff.id')
            ->join('branches', 'sales.branch_id', '=', 'branches.id')
            ->whereDate('sales.sale_date', $date)
            ->whereNotNull('sale_items.branch_staff_id')
            ->when($user->isCashier(), fn ($q) => $q->where('sales.branch_id', $user->branch_id))
            ->when($branch && $user->isOwner(), fn ($q) => $q->where('sales.branch_id', $branch))
            ->when($cashierId, fn ($q) => $q->where('sales.user_id', $cashierId))
            ->selectRaw('sales.branch_id, branches.name as branch_name, sale_items.branch_staff_id, branch_staff.name as staff_name, SUM(sale_items.quantity) as services_rendered, SUM(sale_items.subtotal) as amount_made')
            ->groupBy('sales.branch_id', 'branches.name', 'sale_items.branch_staff_id', 'branch_staff.name')
            ->orderBy('branches.name')
            ->orderByDesc('amount_made')
            ->get();

        // Recent 10 sales
        $recentSales = (clone $query)->latest()->limit(10)->get();

        $branches  = $user->isOwner() ? Branch::where('is_active', true)->get() : collect();
        $cashiers  = $user->isOwner()
            ? User::whereIn('role', ['cashier', 'owner'])
                ->when($branch, fn ($q) => $q->where('branch_id', $branch))
                ->orderBy('name')->get()
            : collect();

        return view('dashboard', compact(
            'totalSales', 'transactionCount', 'cashSales',
            'momoSales', 'topItems',
            'salesByBranch', 'recentSales', 'branches', 'cashiers',
            'staffPerformanceByBranch', 'date', 'branch', 'cashierId'
        ));
    }
}
