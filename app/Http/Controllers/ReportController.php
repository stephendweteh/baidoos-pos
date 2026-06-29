<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Branch;
use App\Models\Item;
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
        $staffFrom = $request->get('staff_from', $from);
        $staffTo   = $request->get('staff_to', $to);
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

        // ── Products vs services segmentation ────────────────────
        $salesByType = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('items', 'sale_items.item_id', '=', 'items.id')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('sales.branch_id', $branchId))
            ->when($cashierId, fn ($q) => $q->where('sales.user_id', $cashierId))
            ->selectRaw("COALESCE(items.type, 'service') as item_type, SUM(sale_items.quantity) as total_qty, SUM(sale_items.subtotal) as total_amount")
            ->groupBy('item_type')
            ->get()
            ->keyBy('item_type');

        $productSalesQty = (int) ($salesByType->get('product')->total_qty ?? 0);
        $productSalesAmount = (float) ($salesByType->get('product')->total_amount ?? 0);
        $serviceSalesQty = (int) ($salesByType->get('service')->total_qty ?? 0);
        $serviceSalesAmount = (float) ($salesByType->get('service')->total_amount ?? 0);

        // ── Current stock snapshot (products only) ───────────────
        $stockSummary = Item::query()
            ->where('type', 'product')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('COALESCE(SUM(stock_quantity), 0) as total_stock')
            ->selectRaw('SUM(CASE WHEN COALESCE(stock_quantity, 0) = 0 THEN 1 ELSE 0 END) as out_of_stock_items')
            ->selectRaw('SUM(CASE WHEN COALESCE(stock_quantity, 0) > 0 AND COALESCE(stock_quantity, 0) <= 5 THEN 1 ELSE 0 END) as low_stock_items')
            ->selectRaw('COUNT(*) as total_products')
            ->first();

        $totalStockRemaining = (int) ($stockSummary->total_stock ?? 0);
        $outOfStockItems = (int) ($stockSummary->out_of_stock_items ?? 0);
        $lowStockItems = (int) ($stockSummary->low_stock_items ?? 0);
        $totalProductItems = (int) ($stockSummary->total_products ?? 0);

        // ── Staff performance (services only) ────────────────────
        $staffPerformance = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('branch_staff', 'sale_items.branch_staff_id', '=', 'branch_staff.id')
            ->join('branches', 'sales.branch_id', '=', 'branches.id')
            ->whereBetween('sales.sale_date', [$staffFrom, $staffTo])
            ->when($branchId, fn ($q) => $q->where('sales.branch_id', $branchId))
            ->when($cashierId, fn ($q) => $q->where('sales.user_id', $cashierId))
            ->selectRaw('sales.branch_id, branches.name as branch_name, sale_items.branch_staff_id, branch_staff.name as staff_name, SUM(sale_items.quantity) as services_rendered, SUM(sale_items.subtotal) as amount_made')
            ->groupBy('sales.branch_id', 'branches.name', 'sale_items.branch_staff_id', 'branch_staff.name')
            ->orderBy('branches.name')
            ->orderByDesc('amount_made')
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

        // ── Product sales and stock remaining list ──────────────
        $cashierNamesSelect = DB::connection()->getDriverName() === 'sqlite'
            ? "COALESCE(group_concat(DISTINCT cashiers.name), '-')"
            : "COALESCE(GROUP_CONCAT(DISTINCT cashiers.name ORDER BY cashiers.name SEPARATOR ', '), '-')";

        $productSalesAndStock = DB::table('items')
            ->join('branches', 'items.branch_id', '=', 'branches.id')
            ->leftJoin('sale_items', 'items.id', '=', 'sale_items.item_id')
            ->leftJoin('sales', function ($join) use ($from, $to, $branchId, $cashierId) {
                $join->on('sales.id', '=', 'sale_items.sale_id')
                    ->whereBetween('sales.sale_date', [$from, $to]);

                if ($branchId) {
                    $join->where('sales.branch_id', $branchId);
                }

                if ($cashierId) {
                    $join->where('sales.user_id', $cashierId);
                }
            })
            ->leftJoin('users as cashiers', 'sales.user_id', '=', 'cashiers.id')
            ->where('items.type', 'product')
            ->when($branchId, fn ($q) => $q->where('items.branch_id', $branchId))
            ->selectRaw('items.id, items.name as product_name, branches.name as branch_name, COALESCE(items.stock_quantity, 0) as stock_remaining')
            ->selectRaw('COALESCE(SUM(CASE WHEN sales.id IS NOT NULL THEN sale_items.quantity ELSE 0 END), 0) as sold_qty')
            ->selectRaw('COALESCE(SUM(CASE WHEN sales.id IS NOT NULL THEN sale_items.subtotal ELSE 0 END), 0) as sold_amount')
            ->selectRaw($cashierNamesSelect.' as cashier_names')
            ->groupBy('items.id', 'items.name', 'branches.name', 'items.stock_quantity')
            ->orderBy('branches.name')
            ->orderBy('items.name')
            ->get();

        // ── Recent sales list ─────────────────────────────────────
        $sales = (clone $base)
            ->with('branch', 'cashier')
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return view('reports.index', compact(
            'from', 'to', 'staffFrom', 'staffTo', 'branchId', 'cashierId', 'branches', 'cashiers',
            'totalRevenue', 'totalDiscount', 'totalTxns', 'avgTxn',
            'byPayment', 'byBranch', 'staffPerformance', 'topItems', 'sales', 'productSalesAndStock',
            'productSalesQty', 'productSalesAmount', 'serviceSalesQty', 'serviceSalesAmount',
            'totalStockRemaining', 'outOfStockItems', 'lowStockItems', 'totalProductItems'
        ));
    }

    public function exportStaffPerformance(Request $request)
    {
        $from      = $request->get('from', today()->startOfMonth()->toDateString());
        $to        = $request->get('to',   today()->toDateString());
        $staffFrom = $request->get('staff_from', $from);
        $staffTo   = $request->get('staff_to', $to);
        $branchId  = $request->get('branch_id');
        $cashierId = $request->get('cashier_id');

        $rows = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('branch_staff', 'sale_items.branch_staff_id', '=', 'branch_staff.id')
            ->join('branches', 'sales.branch_id', '=', 'branches.id')
            ->whereBetween('sales.sale_date', [$staffFrom, $staffTo])
            ->when($branchId, fn ($q) => $q->where('sales.branch_id', $branchId))
            ->when($cashierId, fn ($q) => $q->where('sales.user_id', $cashierId))
            ->selectRaw('branches.name as branch_name, branch_staff.name as staff_name, SUM(sale_items.quantity) as services_rendered, SUM(sale_items.subtotal) as amount_made')
            ->groupBy('branches.name', 'branch_staff.name')
            ->orderBy('branches.name')
            ->orderByDesc('amount_made')
            ->get();

        $filename = 'staff-performance-' . $staffFrom . '-to-' . $staffTo . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($rows) {
            $fh = fopen('php://output', 'w');

            fputcsv($fh, [
                'Branch', 'Staff', 'Services Rendered', 'Revenue Made (GHS)',
            ]);

            foreach ($rows as $row) {
                fputcsv($fh, [
                    $row->branch_name,
                    $row->staff_name,
                    $row->services_rendered,
                    number_format($row->amount_made, 2, '.', ''),
                ]);
            }

            fclose($fh);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportStaffPerformancePdf(Request $request)
    {
        $from      = $request->get('from', today()->startOfMonth()->toDateString());
        $to        = $request->get('to',   today()->toDateString());
        $staffFrom = $request->get('staff_from', $from);
        $staffTo   = $request->get('staff_to', $to);
        $branchId  = $request->get('branch_id');
        $cashierId = $request->get('cashier_id');

        $rows = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('branch_staff', 'sale_items.branch_staff_id', '=', 'branch_staff.id')
            ->join('branches', 'sales.branch_id', '=', 'branches.id')
            ->whereBetween('sales.sale_date', [$staffFrom, $staffTo])
            ->when($branchId, fn ($q) => $q->where('sales.branch_id', $branchId))
            ->when($cashierId, fn ($q) => $q->where('sales.user_id', $cashierId))
            ->selectRaw('branches.name as branch_name, branch_staff.name as staff_name, SUM(sale_items.quantity) as services_rendered, SUM(sale_items.subtotal) as amount_made')
            ->groupBy('branches.name', 'branch_staff.name')
            ->orderBy('branches.name')
            ->orderByDesc('amount_made')
            ->get();

        $summary = [
            'totalServices' => $rows->sum('services_rendered'),
            'totalRevenue' => $rows->sum('amount_made'),
            'totalRows' => $rows->count(),
        ];

        $pdf = Pdf::loadView('reports.staff-performance-pdf', [
            'staffFrom' => $staffFrom,
            'staffTo' => $staffTo,
            'rows' => $rows,
            'summary' => $summary,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('staff-performance-' . $staffFrom . '-to-' . $staffTo . '.pdf');
    }

    public function export(Request $request)
    {
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

    public function exportPdf(Request $request)
    {
        $from      = $request->get('from', today()->startOfMonth()->toDateString());
        $to        = $request->get('to',   today()->toDateString());
        $branchId  = $request->get('branch_id');
        $cashierId = $request->get('cashier_id');

        $base = Sale::query()
            ->whereBetween('sale_date', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($cashierId, fn ($q) => $q->where('user_id', $cashierId));

        $sales = (clone $base)
            ->with('branch', 'cashier', 'items')
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->get();

        $summary = [
            'totalRevenue' => (clone $base)->sum('total'),
            'totalDiscount' => (clone $base)->sum('discount'),
            'totalTxns' => (clone $base)->count(),
        ];

        $pdf = Pdf::loadView('reports.pdf', [
            'from' => $from,
            'to' => $to,
            'sales' => $sales,
            'summary' => $summary,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('sales-report-' . $from . '-to-' . $to . '.pdf');
    }
}
