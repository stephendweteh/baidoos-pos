<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Mail\OwnerSaleAlertMail;
use App\Mail\SaleReceiptMail;
use App\Models\BranchStaff;
use App\Models\Customer;
use App\Models\DayClosing;
use App\Models\Item;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Services\ArkeselSmsService;
use App\Services\MtnMomoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

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

        $branchStaff = $branch
            ? BranchStaff::where('branch_id', $branch->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
            : collect();

        return view('pos.sale', compact(
            'branch', 'items', 'todayClosed', 'todaySales', 'todayTotal', 'branchStaff'
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
            'items'              => 'required|array|min:1',
            'items.*.id'         => 'nullable|exists:items,id',
            'items.*.is_custom'  => 'nullable|boolean',
            'items.*.custom_name'=> 'nullable|string|max:100',
            'items.*.type'       => 'nullable|in:service,product',
            'items.*.price'      => 'nullable|numeric|min:0',
            'items.*.variation'  => 'nullable|string|max:80',
            'items.*.qty'        => 'required|integer|min:1',
            'items.*.staff_id'   => 'nullable|exists:branch_staff,id',
            'payment_method'     => 'required|in:cash,mobile_money,mtn_momo',
            'momo_ref'           => 'required_if:payment_method,mobile_money|nullable|string|max:100',
            'discount'           => 'nullable|numeric|min:0',
            'customer_name'      => 'required|string|max:100',
            'customer_id'        => 'nullable|exists:customers,id',
            'customer_phone'     => 'nullable|string|max:20',
            'customer_email'     => 'nullable|email|max:150',
            'notes'              => 'nullable|string|max:255',
        ]);

        if ($request->payment_method === 'mtn_momo' && !$request->filled('customer_phone')) {
            return back()->withInput()->withErrors([
                'customer_phone' => 'Customer phone is required for MTN MoMo payment.',
            ]);
        }

        if ($request->payment_method === 'mobile_money' && !$request->filled('momo_ref')) {
            return back()->withInput()->withErrors([
                'momo_ref' => 'MoMo Ref / Transaction ID is required for Mobile Money payment.',
            ]);
        }

        $sale = null;
        $isMtnMomo = $request->payment_method === 'mtn_momo';

        DB::transaction(function () use ($request, $branchId, $user, &$sale) {
            $subtotal = 0;
            $lineItems = [];

            foreach ($request->items as $line) {
                $isCustom = filter_var($line['is_custom'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $requestedQty = (int) ($line['qty'] ?? 0);
                if ($requestedQty < 1) {
                    throw ValidationException::withMessages([
                        'items' => ['Each line item quantity must be at least 1.'],
                    ]);
                }

                if ($isCustom) {
                    $customName = trim((string) ($line['custom_name'] ?? ''));
                    $customType = (string) ($line['type'] ?? 'service');
                    $customPrice = (float) ($line['price'] ?? -1);
                    $variation = trim((string) ($line['variation'] ?? ''));

                    if ($customName === '' || !in_array($customType, ['service', 'product'], true) || $customPrice < 0) {
                        throw ValidationException::withMessages([
                            'items' => ['Each custom item must have a valid name, type, and price.'],
                        ]);
                    }

                    $staffId = null;
                    if ($customType === 'service' && !empty($line['staff_id'])) {
                        $staffId = BranchStaff::where('id', $line['staff_id'])
                            ->where('branch_id', $branchId)
                            ->where('is_active', true)
                            ->value('id');

                        if (!$staffId) {
                            throw ValidationException::withMessages([
                                'items' => ['Selected staff member is not valid for this branch.'],
                            ]);
                        }
                    }

                    $displayName = $customName;
                    if ($customType === 'service' && $variation !== '') {
                        $displayName .= ' (' . $variation . ')';
                    }

                    $lineSubtotal = $customPrice * $requestedQty;
                    $subtotal += $lineSubtotal;

                    $lineItems[] = [
                        'item_id'         => null,
                        'branch_staff_id' => $staffId,
                        'item_name'       => $displayName,
                        'item_price'      => $customPrice,
                        'quantity'        => $requestedQty,
                        'subtotal'        => $lineSubtotal,
                    ];

                    continue;
                }

                if (empty($line['id'])) {
                    throw ValidationException::withMessages([
                        'items' => ['Each selected item must include a valid item ID.'],
                    ]);
                }

                $item = Item::where('id', $line['id'])
                    ->where('branch_id', $branchId)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->firstOrFail();

                $variation = trim((string) ($line['variation'] ?? ''));
                $staffId = null;
                if ($item->type === 'service' && $item->assign_staff) {
                    $staffId = BranchStaff::where('id', $line['staff_id'] ?? null)
                        ->where('branch_id', $branchId)
                        ->where('is_active', true)
                        ->value('id');

                    if (!$staffId) {
                        throw ValidationException::withMessages([
                            'items' => ['Assigned staff member is required for each selected service.'],
                        ]);
                    }
                }

                if ($item->type === 'product') {
                    $availableStock = (int) ($item->stock_quantity ?? 0);
                    if ($requestedQty > $availableStock) {
                        throw ValidationException::withMessages([
                            'items' => ["Not enough stock for {$item->name}. Available: {$availableStock}."],
                        ]);
                    }

                    $item->decrement('stock_quantity', $requestedQty);
                }

                $lineSubtotal = $item->price * $requestedQty;
                $subtotal += $lineSubtotal;

                $displayName = $item->name;
                if ($item->type === 'service' && $variation !== '') {
                    $displayName .= ' (' . $variation . ')';
                }

                $lineItems[] = [
                    'item_id'         => $item->id,
                    'branch_staff_id' => $staffId,
                    'item_name'       => $displayName,
                    'item_price'      => $item->price,
                    'quantity'        => $requestedQty,
                    'subtotal'        => $lineSubtotal,
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
                'momo_ref'       => $request->payment_method === 'mobile_money' ? $request->momo_ref : null,
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

            $this->syncCustomerDirectory($request);
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
            $this->sendOwnerAlerts($sale);
        }

        return redirect()->route('pos.receipt', $sale->id)
            ->with('success', 'Sale recorded successfully!');
    }

    private function syncCustomerDirectory(Request $request): void
    {
        $name = trim((string) $request->input('customer_name', ''));
        if ($name === '') {
            return;
        }

        $phone = trim((string) $request->input('customer_phone', ''));
        $email = strtolower(trim((string) $request->input('customer_email', '')));

        $customer = null;
        $customerId = $request->input('customer_id');

        if (!empty($customerId)) {
            $customer = Customer::find($customerId);
        }

        if (!$customer && $phone !== '') {
            $customer = Customer::where('phone', $phone)->first();
        }

        if (!$customer && $email !== '') {
            $customer = Customer::whereRaw('LOWER(email) = ?', [$email])->first();
        }

        if (!$customer) {
            $customer = Customer::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        }

        if ($customer) {
            $updates = [
                'name' => $name,
                'is_active' => true,
            ];

            if ($phone !== '' && $customer->phone !== $phone) {
                $phoneOwner = Customer::where('phone', $phone)
                    ->where('id', '!=', $customer->id)
                    ->exists();
                if (!$phoneOwner) {
                    $updates['phone'] = $phone;
                }
            }

            if ($email !== '') {
                $updates['email'] = $email;
            }

            $customer->update($updates);
            return;
        }

        Customer::create([
            'name' => $name,
            'phone' => $phone !== '' ? $phone : null,
            'email' => $email !== '' ? $email : null,
            'is_active' => true,
        ]);
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
                $this->sendOwnerAlerts($sale);
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
                $this->sendOwnerAlerts($sale);
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

    private function sendOwnerAlerts(Sale $sale): void
    {
        $alertData = [
            'sale_id'        => $sale->id,
            'branch_name'    => $sale->branch->name,
            'cashier_name'   => $sale->cashier->name,
            'customer_name'  => $sale->customer_name,
            'items'          => $sale->items->toArray(),
            'discount'       => number_format($sale->discount, 2),
            'total'          => number_format($sale->total, 2),
            'payment_method' => $sale->payment_method,
            'time'           => $sale->created_at->format('d M Y h:i A'),
        ];

        // Email all owners and superadmins
        $owners = User::whereIn('role', ['owner', 'superadmin'])->whereNotNull('email')->get();
        foreach ($owners as $owner) {
            try {
                Mail::to($owner->email)->send(new OwnerSaleAlertMail($sale));
            } catch (\Throwable $e) {
                Log::error('Owner sale alert email failed', [
                    'sale_id' => $sale->id,
                    'email'   => $owner->email,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        // SMS each owner/superadmin who has a phone number set
        $ownersWithPhone = User::whereIn('role', ['owner', 'superadmin'])->whereNotNull('phone')->where('phone', '!=', '')->get();
        foreach ($ownersWithPhone as $owner) {
            try {
                $smsSent = (new ArkeselSmsService())->sendOwnerAlertSms($owner->phone, $alertData);
                if (!$smsSent) {
                    Log::warning('Owner sale alert SMS returned false', [
                        'sale_id' => $sale->id,
                        'phone' => $owner->phone,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('Owner sale alert SMS failed', [
                    'sale_id' => $sale->id,
                    'phone'   => $owner->phone,
                    'error'   => $e->getMessage(),
                ]);
            }
        }
    }

    private function sendCustomerReceiptNotifications(Sale $sale): void
    {
        if (!empty($sale->customer_phone)) {
            try {
                $smsSent = (new ArkeselSmsService())->sendReceiptSms($sale->customer_phone, [
                    'customer_name'  => $sale->customer_name,
                    'branch_name'    => $sale->branch->name,
                    'sale_id'        => $sale->id,
                    'items'          => $sale->items->toArray(),
                    'discount'       => number_format($sale->discount, 2),
                    'total'          => number_format($sale->total, 2),
                    'payment_method' => $sale->payment_method,
                ]);
                if (!$smsSent) {
                    Log::warning('Receipt SMS returned false', [
                        'sale_id' => $sale->id,
                        'phone' => $sale->customer_phone,
                    ]);
                }
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
