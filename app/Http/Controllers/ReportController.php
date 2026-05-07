<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:owner']);
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        // Date range defaults to current month
        $from      = $request->get('from', today()->startOfMonth()->toDateString());
        $to        = $request->get('to',   today()->toDateString());
        $branchId  = $request->get('branch_id');
        $cashierId = $request->get('cashier_id');

        $branches = Branch::where('is_active', true)->get();
        $cashiers = User::whereIn('role', ['cashier', 'owner'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')->get();

        // Base query — scoped by range and optional branch/cashier
        $base = Sale::whereBetween('sale_date', [$from, $to])
            ->when($branchId,  fn ($q) => $q->where('branch_id', $branchId))
            ->when($cashierId, fn ($q) => $q->where('user_id', $cashierId));

        // ── KPI summary ────────────────────────────────────────────
        $totalRevenue    = (clone $base)->sum('total');
        $totalDiscount   = (clone $base)->sum('discount');
        $totalTxns       = (clone $base)->count();
        $avgTxn          = $totalTxns > 0 ? $totalRevenue / $totalTxns : 0;

        // ── By payment method ─────────────────────────────────────
        $byPayment = (clone $base)
            ->selectRaw('payment_method, SUM(total) as revenue, COUNT(*) as txns')
            ->groupBy('payment_method')
            ->get()
            ->keyBy('payment_method');

        // ── By branch ─────────────────────────────────────────────
        $byBranch = (clone $base)
            ->selectRaw('branch_id, SUM(total) as revenue, SUM(discount) as discount, COUNT(*) as txns')
            ->groupBy('branch_id')
            ->with('branch')
            ->get();

        // ── Daily trend ───────────────────────────────────────────
        $daily = (clone $base)
            ->selectRaw('sale_date, SUM(total) as revenue, SUM(discount) as discount, COUNT(*) as txns')
            ->groupBy('sale_date')
            ->orderBy('sale_date')
            ->get();

        // ── Top 10 items ──────────────────────────────────────────
        $topItems = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->when($branchId,  fn ($q) => $q->where('sales.branch_id', $branchId))
            ->when($cashierId, fn ($q) => $q->where('sales.user_id', $cashierId))
            ->selectRaw('item_name, SUM(sale_items.quantity) as total_qty, SUM(sale_items.subtotal) as total_amount')
            ->groupBy('item_name')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->get();

        // ── Recent sales list ─────────────────────────────────────
        $sales = (clone $base)
            ->with('branch', 'cashier')
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return view('reports.index', compact(
            'from', 'to', 'branchId', 'cashierId', 'branches', 'cashiers',
            'totalRevenue', 'totalDiscount', 'totalTxns', 'avgTxn',
            'byPayment', 'byBranch', 'daily', 'topItems', 'sales'
        ));
    }

    public function export(Request $request)
    {
        $user = auth()->user();

        $from      = $request->get('from', today()->startOfMonth()->toDateString());
        $to        = $request->get('to',   today()->toDateString());
        $branchId  = $request->get('branch_id');
        $cashierId = $request->get('cashier_id');

        $sales = Sale::with('branch', 'cashier', 'items')
            ->whereBetween('sale_date', [$from, $to])
            ->when($branchId,  fn ($q) => $q->where('branch_id', $branchId))
            ->when($cashierId, fn ($q) => $q->where('user_id', $cashierId))
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->get();

        $filename = 'sales-report-' . $from . '-to-' . $to . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($sales) {
            $fh = fopen('php://output', 'w');
            // Header row
            fputcsv($fh, [
                'Sale #', 'Date', 'Branch', 'Customer', 'Phone', 'Email',
                'Items', 'Subtotal (GHS)', 'Discount (GHS)', 'Total (GHS)',
                'Payment Method', 'Cashier', 'Notes',
            ]);
            foreach ($sales as $s) {
                $itemsSummary = $s->items->map(fn ($i) => $i->item_name . ' x' . $i->quantity)->implode(' | ');
                fputcsv($fh, [
                    $s->id,
                    $s->sale_date->toDateString(),
                    $s->branch->name ?? '',
                    $s->customer_name ?? '',
                    $s->customer_phone ?? '',
                    $s->customer_email ?? '',
                    $itemsSummary,
                    number_format($s->subtotal, 2),
                    number_format($s->discount, 2),
                    number_format($s->total, 2),
                    strtoupper($s->payment_method),
                    $s->cashier->name ?? '',
                    $s->notes ?? '',
                ]);
            }
            fclose($fh);
        };

        return response()->stream($callback, 200, $headers);
    }
}
