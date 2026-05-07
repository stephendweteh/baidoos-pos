<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Mail\SaleReceiptMail;
use App\Models\DayClosing;
use App\Models\Item;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\ArkeselSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SaleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user   = auth()->user();
        $branch = $user->isCashier() ? $user->branch : null;

        if ($user->isCashier() && !$branch) {
            return redirect()->route('dashboard')
                ->with('error', 'You are not assigned to a branch. Contact the owner.');
        }

        // Check if today is already closed for this branch
        $todayClosed = $branch
            ? DayClosing::where('branch_id', $branch->id)
                ->whereDate('closing_date', today())
                ->exists()
            : false;

        $items = $branch
            ? Item::where('branch_id', $branch->id)->where('is_active', true)->get()
            : collect();

        // Today's sales for the sidebar summary
        $todaySales = $branch
            ? Sale::where('branch_id', $branch->id)
                ->whereDate('sale_date', today())
                ->latest()
                ->limit(10)
                ->get()
            : collect();

        $todayTotal = $branch
            ? Sale::where('branch_id', $branch->id)
                ->whereDate('sale_date', today())
                ->sum('total')
            : 0;

        return view('pos.sale', compact(
            'branch', 'items', 'todayClosed', 'todaySales', 'todayTotal'
        ));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        if ($user->isCashier() && !$user->branch_id) {
            return back()->with('error', 'You are not assigned to a branch.');
        }

        $branchId = $user->isCashier() ? $user->branch_id : $request->input('branch_id');

        // Check if day is closed
        if (DayClosing::where('branch_id', $branchId)->whereDate('closing_date', today())->exists()) {
            return back()->with('error', 'Today is already closed for this branch. No new sales can be added.');
        }

        $request->validate([
            'items'            => 'required|array|min:1',
            'items.*.id'       => 'required|exists:items,id',
            'items.*.qty'      => 'required|integer|min:1',
            'payment_method'   => 'required|in:cash,transfer,card',
            'discount'         => 'nullable|numeric|min:0',
            'customer_name'    => 'required|string|max:100',
            'customer_phone'   => 'nullable|string|max:20',
            'customer_email'   => 'nullable|email|max:150',
            'notes'            => 'nullable|string|max:255',
        ]);

        $sale = null;

        DB::transaction(function () use ($request, $branchId, $user, &$sale) {
            $subtotal = 0;
            $lineItems = [];

            foreach ($request->items as $line) {
                $item = Item::where('id', $line['id'])
                    ->where('branch_id', $branchId)
                    ->where('is_active', true)
                    ->firstOrFail();

                $lineSubtotal = $item->price * $line['qty'];
                $subtotal += $lineSubtotal;

                $lineItems[] = [
                    'item_id'    => $item->id,
                    'item_name'  => $item->name,
                    'item_price' => $item->price,
                    'quantity'   => $line['qty'],
                    'subtotal'   => $lineSubtotal,
                ];
            }

            $discount = (float) ($request->discount ?? 0);
            $total    = max(0, $subtotal - $discount);

            $sale = Sale::create([
                'branch_id'      => $branchId,
                'user_id'        => $user->id,
                'sale_date'      => today(),
                'subtotal'       => $subtotal,
                'discount'       => $discount,
                'total'          => $total,
                'payment_method' => $request->payment_method,
                'customer_name'  => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_email' => $request->customer_email,
                'notes'          => $request->notes,
            ]);

            foreach ($lineItems as $li) {
                $li['sale_id'] = $sale->id;
                SaleItem::create($li);
            }
        });

        if ($sale) {
            $sale->load('items', 'branch', 'cashier');

            if ($request->filled('customer_phone')) {
                (new ArkeselSmsService())->sendReceiptSms($request->customer_phone, [
                    'customer_name'  => $sale->customer_name,
                    'branch_name'    => $sale->branch->name,
                    'sale_id'        => $sale->id,
                    'items'          => $sale->items->toArray(),
                    'discount'       => number_format($sale->discount, 2),
                    'total'          => number_format($sale->total, 2),
                    'payment_method' => $sale->payment_method,
                ]);
            }

            if ($request->filled('customer_email')) {
                try {
                    Mail::to($request->customer_email)->send(new SaleReceiptMail($sale));
                } catch (\Throwable $e) {
                    Log::error('Receipt email failed to send', [
                        'sale_id' => $sale->id,
                        'email' => $request->customer_email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return redirect()->route('pos.receipt', $sale->id)
            ->with('success', 'Sale recorded successfully!');
    }

    public function show(Sale $sale)
    {
        $user = auth()->user();
        if ($user->isCashier() && $sale->branch_id !== $user->branch_id) {
            abort(403);
        }
        $sale->load('items', 'branch', 'cashier');
        return view('pos.receipt', compact('sale'));
    }
}
