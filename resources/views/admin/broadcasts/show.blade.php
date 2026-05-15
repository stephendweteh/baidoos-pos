@extends('layouts.app')
@section('title', $broadcast->title)
@section('page-title', $broadcast->title)

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                {{-- Status Badge --}}
                <div class="mb-3">
                    @if($broadcast->status === 'draft')
                        <span class="badge bg-secondary-soft text-secondary" style="font-size:.9rem">Draft</span>
                    @elseif($broadcast->status === 'sending')
                        <span class="badge bg-warning-soft text-warning" style="font-size:.9rem">Sending...</span>
                    @elseif($broadcast->status === 'completed')
                        <span class="badge bg-success-soft text-success" style="font-size:.9rem">Completed</span>
                    @else
                        <span class="badge bg-danger-soft text-danger" style="font-size:.9rem">Failed</span>
                    @endif
                </div>

                {{-- Title --}}
                <h5 class="mb-4">{{ $broadcast->title }}</h5>

                {{-- Message --}}
                <div class="alert alert-light p-3 mb-4" style="white-space: pre-wrap; font-family: monospace; font-size:.9rem">
                    {{ $broadcast->message }}
                </div>

                {{-- Details Grid --}}
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Channel</small>
                            <strong>
                                @if($broadcast->channel === 'sms')
                                    📞 SMS Only
                                @elseif($broadcast->channel === 'email')
                                    ✉️ Email Only
                                @else
                                    📞 ✉️ SMS & Email
                                @endif
                            </strong>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Created By</small>
                            <strong>{{ $broadcast->creator->name }}</strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Recipients</small>
                            <strong>{{ $broadcast->total_recipients }} customers</strong>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Created</small>
                            <strong>{{ $broadcast->created_at->format('M d, Y h:i A') }}</strong>
                        </div>
                    </div>
                </div>

                {{-- Delivery Stats (if sent) --}}
                @if($broadcast->status !== 'draft')
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stat-card text-center">
                            <div class="text-success" style="font-size:1.5rem; font-weight:bold">{{ $broadcast->sent_count }}</div>
                            <small class="text-muted">Delivered</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card text-center">
                            <div class="text-danger" style="font-size:1.5rem; font-weight:bold">{{ $broadcast->failed_count }}</div>
                            <small class="text-muted">Failed</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card text-center">
                            <div class="text-info" style="font-size:1.5rem; font-weight:bold">
                                @if($broadcast->total_recipients > 0)
                                    {{ round(($broadcast->sent_count / $broadcast->total_recipients) * 100) }}%
                                @else
                                    0%
                                @endif
                            </div>
                            <small class="text-muted">Success Rate</small>
                        </div>
                    </div>
                </div>

                @if($broadcast->sent_at)
                <div class="alert alert-info py-2 px-3" style="font-size:.85rem">
                    <i class="bi bi-check-circle"></i>
                    Sent on <strong>{{ $broadcast->sent_at->format('M d, Y h:i A') }}</strong>
                </div>
                @endif
                @endif
            </div>
        </div>
    </div>

    {{-- Sidebar: Actions --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <strong>Actions</strong>
            </div>
            <div class="card-body">
                @if($broadcast->status === 'draft')
                <form method="POST" action="{{ route('admin.broadcasts.send', $broadcast) }}" 
                      onsubmit="return confirm('Send this broadcast to all {{ $broadcast->total_recipients }} customers?')">
                    @csrf
                    <button type="submit" class="btn btn-success w-100 mb-2">
                        <i class="bi bi-paper-plane"></i> Send Now
                    </button>
                </form>

                <a href="{{ route('admin.broadcasts.index') }}" class="btn btn-outline-secondary w-100 mb-2">
                    <i class="bi bi-arrow-left"></i> Back
                </a>

                <form method="POST" action="{{ route('admin.broadcasts.destroy', $broadcast) }}" 
                      onsubmit="return confirm('Delete this draft?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger w-100">
                        <i class="bi bi-trash"></i> Delete Draft
                    </button>
                </form>
                @else
                <a href="{{ route('admin.broadcasts.index') }}" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-arrow-left"></i> Back to Broadcasts
                </a>
                @endif
            </div>
        </div>

        {{-- Tips --}}
        <div class="alert alert-light mt-3 p-3" style="font-size:.85rem">
            <strong>💡 Tips:</strong>
            <ul class="mb-0 mt-2 ps-3">
                <li>SMS: Customers must have a phone number</li>
                <li>Email: Customers must have an email address</li>
                <li>Delivery may take a few moments</li>
                <li>Check logs if delivery fails</li>
            </ul>
        </div>
    </div>
</div>

<style>
.stat-card {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
}
</style>
@endsection
