<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
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

        // ── Staff performance (services only) ────────────────────
        $staffPerformance = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('branch_staff', 'sale_items.branch_staff_id', '=', 'branch_staff.id')
            ->join('branches', 'sales.branch_id', '=', 'branches.id')
            ->whereBetween('sales.sale_date', [$staffFrom, $staffTo])
            ->when($branchId, fn ($q) => $q->where('sales.branch_id', $branchId))
            ->when($cashierId, fn ($q) => $q->where('sales.user_id', $cashierId))
            ->selectRaw('sales.sale_date as performance_date, sales.branch_id, branches.name as branch_name, sale_items.branch_staff_id, branch_staff.name as staff_name, SUM(sale_items.quantity) as services_rendered, SUM(sale_items.subtotal) as amount_made')
            ->groupBy('sales.sale_date', 'sales.branch_id', 'branches.name', 'sale_items.branch_staff_id', 'branch_staff.name')
            ->orderBy('sales.sale_date', 'desc')
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
            'byPayment', 'byBranch', 'staffPerformance', 'topItems', 'sales'
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
            ->selectRaw('sales.sale_date as performance_date, branches.name as branch_name, branch_staff.name as staff_name, SUM(sale_items.quantity) as services_rendered, SUM(sale_items.subtotal) as amount_made')
            ->groupBy('sales.sale_date', 'branches.name', 'branch_staff.name')
            ->orderBy('sales.sale_date', 'desc')
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
                'Date', 'Branch', 'Staff', 'Services Rendered', 'Revenue Made (GHS)',
            ]);

            foreach ($rows as $row) {
                fputcsv($fh, [
                    $row->performance_date,
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
            ->selectRaw('sales.sale_date as performance_date, branches.name as branch_name, branch_staff.name as staff_name, SUM(sale_items.quantity) as services_rendered, SUM(sale_items.subtotal) as amount_made')
            ->groupBy('sales.sale_date', 'branches.name', 'branch_staff.name')
            ->orderBy('sales.sale_date', 'desc')
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
