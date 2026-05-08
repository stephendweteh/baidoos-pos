<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Mail\SaleReceiptMail;
use App\Models\DayClosing;
use App\Models\Item;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\ArkeselSmsService;
use App\Services\MtnMomoService;
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
            'payment_method'   => 'required|in:cash,mtn_momo',
            'discount'         => 'nullable|numeric|min:0',
            'customer_name'    => 'required|string|max:100',
            'customer_phone'   => 'nullable|string|max:20',
            'customer_email'   => 'nullable|email|max:150',
            'notes'            => 'nullable|string|max:255',
        ]);

        if ($request->payment_method === 'mtn_momo' && !$request->filled('customer_phone')) {
            return back()->withInput()->withErrors([
                'customer_phone' => 'Customer phone is required for MTN MoMo payment.',
            ]);
        }

        $sale = null;
        $isMtnMomo = $request->payment_method === 'mtn_momo';

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
            $isMtnMomo = $request->payment_method === 'mtn_momo';

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
                'payment_status' => $isMtnMomo ? 'pending' : 'success',
                'momo_status'    => $isMtnMomo ? 'PENDING' : null,
            ]);

            foreach ($lineItems as $li) {
                $li['sale_id'] = $sale->id;
                SaleItem::create($li);
            }
        });

        if ($sale && $isMtnMomo) {
            try {
                $momo = app(MtnMomoService::class);
                $payment = $momo->requestToPay(
                    (string) $sale->id,
                    (float) $sale->total,
                    (string) $request->customer_phone,
                    (string) $sale->customer_name
                );

                $sale->update([
                    'payment_reference' => $payment['reference_id'],
                    'payer_msisdn'      => $payment['msisdn'],
                    'momo_status'       => 'PENDING',
                ]);

                $isSandbox = strtolower((string) config('services.mtn_momo.target_environment', 'sandbox')) === 'sandbox';
                $message = $isSandbox
                    ? 'Sandbox payment initiated. No real phone prompt is sent in sandbox; use Check MTN Status to confirm.'
                    : 'MTN MoMo prompt sent. Ask customer to approve and enter PIN on their phone.';

                return redirect()->route('pos.receipt', $sale->id)
                    ->with('warning', $message);
            } catch (\Throwable $e) {
                Log::error('MTN MoMo RequestToPay failed', [
                    'sale_id' => $sale->id,
                    'error' => $e->getMessage(),
                ]);

                $sale->update([
                    'payment_status' => 'failed',
                    'momo_status' => 'FAILED',
                ]);

                return back()->withInput()->with('error', 'Unable to start MTN MoMo payment. Please try again.');
            }
        }

        if ($sale) {
            $sale->load('items', 'branch', 'cashier');
            $this->sendCustomerReceiptNotifications($sale);
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

    public function momoStatus(Sale $sale)
    {
        $user = auth()->user();
        if ($user->isCashier() && $sale->branch_id !== $user->branch_id) {
            abort(403);
        }

        if ($sale->payment_method !== 'mtn_momo') {
            return response()->json(['ok' => false, 'message' => 'Not an MTN MoMo payment.'], 422);
        }

        if (!$sale->payment_reference) {
            return response()->json(['ok' => false, 'message' => 'Missing MTN payment reference.'], 422);
        }

        try {
            $status = app(MtnMomoService::class)->getRequestStatus($sale->payment_reference);
            $momoStatus = strtoupper($status['status'] ?? 'PENDING');

            if ($momoStatus === 'SUCCESSFUL' && $sale->payment_status !== 'success') {
                $sale->update([
                    'payment_status' => 'success',
                    'momo_status' => $momoStatus,
                ]);
                $sale->refresh()->load('items', 'branch', 'cashier');
                $this->sendCustomerReceiptNotifications($sale);
            } elseif (in_array($momoStatus, ['FAILED', 'REJECTED', 'TIMEOUT'], true)) {
                $sale->update([
                    'payment_status' => 'failed',
                    'momo_status' => $momoStatus,
                ]);
            } else {
                $sale->update(['momo_status' => $momoStatus]);
            }

            $sale->refresh();

            return response()->json([
                'ok' => true,
                'payment_status' => $sale->payment_status,
                'momo_status' => $sale->momo_status,
                'message' => $this->momoStatusMessage($sale->payment_status, $sale->momo_status),
            ]);
        } catch (\Throwable $e) {
            Log::error('MTN MoMo status check failed', [
                'sale_id' => $sale->id,
                'payment_reference' => $sale->payment_reference,
                'error' => $e->getMessage(),
            ]);

            if (str_contains($e->getMessage(), 'RESOURCE_NOT_FOUND')) {
                $sale->update([
                    'payment_status' => 'failed',
                    'momo_status' => 'NOT_FOUND',
                ]);

                return response()->json([
                    'ok' => true,
                    'payment_status' => 'failed',
                    'momo_status' => 'NOT_FOUND',
                    'message' => 'MTN could not find this transaction. Please start a new MoMo payment.',
                ]);
            }

            return response()->json([
                'ok' => false,
                'message' => 'Could not verify MTN payment status. Please try again.',
            ], 500);
        }
    }

    public function momoWebhook(Request $request)
    {
        $referenceId = $request->header('X-Reference-Id') ?: $request->input('referenceId');

        if (!$referenceId) {
            return response()->json(['ok' => false, 'message' => 'Reference ID missing.'], 422);
        }

        $sale = Sale::where('payment_reference', $referenceId)->first();
        if (!$sale) {
            return response()->json(['ok' => false, 'message' => 'Sale not found.'], 404);
        }

        try {
            $status = strtoupper((string) $request->input('status', 'PENDING'));

            if ($status === 'SUCCESSFUL' && $sale->payment_status !== 'success') {
                $sale->update([
                    'payment_status' => 'success',
                    'momo_status' => $status,
                ]);
                $sale->refresh()->load('items', 'branch', 'cashier');
                $this->sendCustomerReceiptNotifications($sale);
            } elseif (in_array($status, ['FAILED', 'REJECTED', 'TIMEOUT'], true)) {
                $sale->update([
                    'payment_status' => 'failed',
                    'momo_status' => $status,
                ]);
            } else {
                $sale->update(['momo_status' => $status]);
            }

            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            Log::error('MTN MoMo webhook processing failed', [
                'reference_id' => $referenceId,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['ok' => false], 500);
        }
    }

    private function sendCustomerReceiptNotifications(Sale $sale): void
    {
        if (!empty($sale->customer_phone)) {
            try {
                (new ArkeselSmsService())->sendReceiptSms($sale->customer_phone, [
                    'customer_name'  => $sale->customer_name,
                    'branch_name'    => $sale->branch->name,
                    'sale_id'        => $sale->id,
                    'items'          => $sale->items->toArray(),
                    'discount'       => number_format($sale->discount, 2),
                    'total'          => number_format($sale->total, 2),
                    'payment_method' => $sale->payment_method,
                ]);
            } catch (\Throwable $e) {
                Log::error('Receipt SMS failed to send', [
                    'sale_id' => $sale->id,
                    'phone' => $sale->customer_phone,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (!empty($sale->customer_email)) {
            try {
                Mail::to($sale->customer_email)->send(new SaleReceiptMail($sale));
            } catch (\Throwable $e) {
                Log::error('Receipt email failed to send', [
                    'sale_id' => $sale->id,
                    'email' => $sale->customer_email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function momoStatusMessage(string $paymentStatus, ?string $momoStatus): string
    {
        $isSandbox = strtolower((string) config('services.mtn_momo.target_environment', 'sandbox')) === 'sandbox';

        if ($paymentStatus === 'success') {
            return 'Payment confirmed successfully.';
        }

        if ($paymentStatus === 'failed') {
            return 'Payment failed (' . ($momoStatus ?: 'UNKNOWN') . ').';
        }

        if ($isSandbox) {
            return 'Sandbox transaction is being processed (' . ($momoStatus ?: 'PENDING') . ').';
        }

        return 'Awaiting customer approval on phone (' . ($momoStatus ?: 'PENDING') . ').';
    }
}
