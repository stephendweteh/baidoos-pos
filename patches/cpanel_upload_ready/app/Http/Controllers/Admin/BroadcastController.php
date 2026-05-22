<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Broadcast;
use App\Models\Customer;
use App\Services\ArkeselSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
            'templateTypes' => [
                'holiday' => 'Holiday Announcement',
                'offday' => 'Off-Day / Public Holiday Notice',
                'maintenance' => 'Maintenance / Downtime Alert',
            ],
            'templateTones' => [
                'friendly' => 'Friendly',
                'formal' => 'Formal',
                'urgent' => 'Urgent',
            ],
        ]);
    }

    /**
     * Generate an AI-style message template that can be edited before saving.
     */
    public function generateTemplate(Request $request)
    {
        $data = $request->validate([
            'template_type' => 'required|in:holiday,offday,maintenance',
            'tone' => 'required|in:friendly,formal,urgent',
            'event_name' => 'nullable|string|max:100',
            'effective_date' => 'nullable|date',
            'reopen_date' => 'nullable|date',
            'extra_note' => 'nullable|string|max:220',
        ]);

        $businessName = config('app.name', 'Baidoos POS');
        $eventName = trim((string) ($data['event_name'] ?? ''));
        $extraNote = trim((string) ($data['extra_note'] ?? ''));
        $effectiveDate = !empty($data['effective_date']) ? date('D, d M Y', strtotime($data['effective_date'])) : null;
        $reopenDate = !empty($data['reopen_date']) ? date('D, d M Y', strtotime($data['reopen_date'])) : null;

        [$title, $message, $channel] = $this->buildTemplate(
            $data['template_type'],
            $data['tone'],
            $businessName,
            $eventName,
            $effectiveDate,
            $reopenDate,
            $extraNote
        );

        return response()->json([
            'title' => Str::limit($title, 150, ''),
            'message' => Str::limit($message, 1000, ''),
            'channel' => $channel,
        ]);
    }

    private function buildTemplate(
        string $templateType,
        string $tone,
        string $businessName,
        string $eventName,
        ?string $effectiveDate,
        ?string $reopenDate,
        string $extraNote
    ): array {
        $friendlyGreeting = "Hello valued customers,";
        $formalGreeting = "Dear customer,";
        $urgentGreeting = "Important notice,";

        $toneMap = [
            'friendly' => [
                'greeting' => $friendlyGreeting,
                'closing' => 'Thank you for always choosing us.',
            ],
            'formal' => [
                'greeting' => $formalGreeting,
                'closing' => 'Thank you for your understanding and continued patronage.',
            ],
            'urgent' => [
                'greeting' => $urgentGreeting,
                'closing' => 'Please plan accordingly and contact us if you need assistance.',
            ],
        ];

        $parts = $toneMap[$tone];
        $nameSegment = $eventName !== '' ? " ({$eventName})" : '';
        $effectiveSegment = $effectiveDate ? " on {$effectiveDate}" : '';
        $reopenSegment = $reopenDate ? " We expect to resume normal service on {$reopenDate}." : '';
        $extraSegment = $extraNote !== '' ? " {$extraNote}" : '';

        if ($templateType === 'holiday') {
            $title = "Holiday Notice{$nameSegment}";
            $message = "{$parts['greeting']} {$businessName} wishes you a happy holiday. Please note that our operations may run on an adjusted schedule{$effectiveSegment}.{$reopenSegment}{$extraSegment} {$parts['closing']}";
            $channel = 'both';
        } elseif ($templateType === 'offday') {
            $title = "Off-Day Schedule Update{$nameSegment}";
            $message = "{$parts['greeting']} this is to inform you that {$businessName} will be closed{$effectiveSegment}.{$reopenSegment}{$extraSegment} {$parts['closing']}";
            $channel = 'both';
        } else {
            $title = "Maintenance Update{$nameSegment}";
            $message = "{$parts['greeting']} {$businessName} will undergo planned maintenance{$effectiveSegment}, which may temporarily affect service availability.{$reopenSegment}{$extraSegment} {$parts['closing']}";
            $channel = 'sms';
        }

        $message = preg_replace('/\s+/', ' ', trim($message));

        return [$title, $message, $channel];
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
            'submit_action' => 'nullable|in:draft,send_now',
        ]);

        $submitAction = $data['submit_action'] ?? 'draft';
        unset($data['submit_action']);

        $data['user_id'] = auth()->id();
        $data['status'] = 'draft';
        $data['total_recipients'] = Customer::active()->count();

        $broadcast = Broadcast::create($data);

        if ($submitAction === 'send_now') {
            return $this->send($broadcast);
        }

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
                        $broadcast->message
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
                    Mail::raw($broadcast->message, function ($message) use ($customer, $broadcast) {
                        $message->to($customer->email)
                                ->subject($broadcast->title);
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
