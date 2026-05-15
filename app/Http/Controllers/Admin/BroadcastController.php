<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Broadcast;
use App\Models\Customer;
use App\Services\ArkeselSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BroadcastController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:owner']);
    }

    /**
     * Display a listing of all broadcasts.
     */
    public function index()
    {
        $broadcasts = Broadcast::latest()->paginate(20);
        return view('admin.broadcasts.index', compact('broadcasts'));
    }

    /**
     * Show the form for creating a new broadcast.
     */
    public function create()
    {
        $customerCount = Customer::active()->count();
        return view('admin.broadcasts.form', [
            'broadcast' => new Broadcast(),
            'customerCount' => $customerCount,
        ]);
    }

    /**
     * Store a newly created broadcast (as draft) in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:150',
            'message' => 'required|string|max:1000',
            'channel' => 'required|in:sms,email,both',
        ]);

        $data['user_id'] = auth()->id();
        $data['status'] = 'draft';
        $data['total_recipients'] = Customer::active()->count();

        Broadcast::create($data);

        return redirect()->route('admin.broadcasts.index')
            ->with('success', 'Broadcast created as draft. Review and send when ready.');
    }

    /**
     * Show broadcast details.
     */
    public function show(Broadcast $broadcast)
    {
        return view('admin.broadcasts.show', compact('broadcast'));
    }

    /**
     * Send a broadcast to all active customers.
     */
    public function send(Broadcast $broadcast)
    {
        if ($broadcast->status !== 'draft') {
            return back()->with('error', 'Only draft broadcasts can be sent.');
        }

        $customers = Customer::active()->get();

        if ($customers->isEmpty()) {
            return back()->with('warning', 'No active customers to send to.');
        }

        $broadcast->update([
            'status' => 'sending',
            'total_recipients' => $customers->count(),
        ]);

        $sentCount = 0;
        $failedCount = 0;
        $smsService = new ArkeselSmsService();

        foreach ($customers as $customer) {
            // Send SMS
            if (in_array($broadcast->channel, ['sms', 'both']) && $customer->phone) {
                try {
                    $smsSent = $smsService->send(
                        $customer->phone,
                        "[Broadcast] {$broadcast->title}\n\n{$broadcast->message}"
                    );

                    if ($smsSent) {
                        $sentCount++;
                    } else {
                        Log::warning('Broadcast SMS failed', [
                            'broadcast_id' => $broadcast->id,
                            'customer_id' => $customer->id,
                            'phone' => $customer->phone,
                        ]);
                        $failedCount++;
                    }
                } catch (\Throwable $e) {
                    Log::error('Broadcast SMS exception', [
                        'broadcast_id' => $broadcast->id,
                        'customer_id' => $customer->id,
                        'error' => $e->getMessage(),
                    ]);
                    $failedCount++;
                }
            }

            // Send Email
            if (in_array($broadcast->channel, ['email', 'both']) && $customer->email) {
                try {
                    Mail::raw("{$broadcast->message}\n\n---\nBroadcast: {$broadcast->title}", function ($message) use ($customer, $broadcast) {
                        $message->to($customer->email)
                                ->subject("[Broadcast] {$broadcast->title}");
                    });

                    $sentCount++;
                } catch (\Throwable $e) {
                    Log::error('Broadcast Email exception', [
                        'broadcast_id' => $broadcast->id,
                        'customer_id' => $customer->id,
                        'error' => $e->getMessage(),
                    ]);
                    $failedCount++;
                }
            }
        }

        $broadcast->update([
            'status' => $failedCount === 0 ? 'completed' : 'failed',
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'sent_at' => now(),
        ]);

        $successMsg = "Broadcast sent! {$sentCount} messages delivered";
        if ($failedCount > 0) {
            $successMsg .= ", {$failedCount} failed (check logs).";
        } else {
            $successMsg .= ".";
        }

        return redirect()->route('admin.broadcasts.show', $broadcast)
            ->with('success', $successMsg);
    }

    /**
     * Delete a broadcast.
     */
    public function destroy(Broadcast $broadcast)
    {
        if ($broadcast->status !== 'draft') {
            return back()->with('error', 'Only draft broadcasts can be deleted.');
        }

        $broadcast->delete();
        return redirect()->route('admin.broadcasts.index')
            ->with('success', 'Broadcast deleted.');
    }
}
