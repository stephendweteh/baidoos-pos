@extends('layouts.app')
@section('title', 'Broadcasts')
@section('page-title', 'Broadcasts')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="text-muted">Send custom messages to all customers</span>
    <a href="{{ route('admin.broadcasts.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-megaphone"></i> New Broadcast
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($broadcasts->count() > 0)
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th style="width: 5%">#</th>
                    <th style="width: 25%">Title</th>
                    <th style="width: 15%">Channel</th>
                    <th style="width: 15%">Recipients</th>
                    <th style="width: 15%">Status</th>
                    <th style="width: 15%">Sent</th>
                    <th style="width: 10%" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($broadcasts as $broadcast)
                <tr>
                    <td class="text-muted">{{ ($broadcasts->currentPage() - 1) * $broadcasts->perPage() + $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $broadcast->title }}</td>
                    <td>
                        @if($broadcast->channel === 'sms')
                            <span class="badge bg-info-soft text-info">📞 SMS</span>
                        @elseif($broadcast->channel === 'email')
                            <span class="badge bg-info-soft text-info">✉️ Email</span>
                        @else
                            <span class="badge bg-info-soft text-info">📞 ✉️ Both</span>
                        @endif
                    </td>
                    <td>{{ $broadcast->total_recipients }}</td>
                    <td>
                        @if($broadcast->status === 'draft')
                            <span class="badge bg-secondary-soft text-secondary">Draft</span>
                        @elseif($broadcast->status === 'sending')
                            <span class="badge bg-warning-soft text-warning">Sending...</span>
                        @elseif($broadcast->status === 'completed')
                            <span class="badge bg-success-soft text-success">Completed</span>
                        @else
                            <span class="badge bg-danger-soft text-danger">Failed</span>
                        @endif
                    </td>
                    <td>
                        @if($broadcast->sent_at)
                            <small class="text-muted">{{ $broadcast->sent_at->format('M d, Y h:i A') }}</small>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.broadcasts.show', $broadcast) }}" class="btn btn-sm btn-outline-secondary" title="View details">
                            <i class="bi bi-eye"></i>
                        </a>
                        @if($broadcast->status === 'draft')
                        <form method="POST" action="{{ route('admin.broadcasts.destroy', $broadcast) }}" class="d-inline"
                              onsubmit="return confirm('Delete this draft?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete draft">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="bi bi-inbox"></i> No broadcasts yet. 
                        <a href="{{ route('admin.broadcasts.create') }}">Create one</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        @if($broadcasts->hasPages())
        <div class="d-flex justify-content-center p-3">
            {{ $broadcasts->links() }}
        </div>
        @endif
        @else
        <div class="text-center text-muted py-5">
            <i class="bi bi-megaphone" style="font-size: 2rem;"></i>
            <p class="mt-2">No broadcasts yet.</p>
            <a href="{{ route('admin.broadcasts.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-megaphone"></i> Send First Broadcast
            </a>
        </div>
        @endif
    </div>
</div>
@endsection

<style>
.badge.bg-info-soft { background-color: rgba(13, 202, 240, 0.15); }
.badge.bg-info-soft.text-info { color: #0dcaf0 !important; }
.badge.bg-success-soft { background-color: rgba(25, 135, 84, 0.15); }
.badge.bg-success-soft.text-success { color: #198754 !important; }
.badge.bg-danger-soft { background-color: rgba(220, 53, 69, 0.15); }
.badge.bg-danger-soft.text-danger { color: #dc3545 !important; }
.badge.bg-warning-soft { background-color: rgba(255, 193, 7, 0.15); }
.badge.bg-warning-soft.text-warning { color: #ffc107 !important; }
.badge.bg-secondary-soft { background-color: rgba(108, 117, 125, 0.15); }
.badge.bg-secondary-soft.text-secondary { color: #6c757d !important; }
</style>
