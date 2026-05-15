@extends('layouts.app')
@section('title', 'Customers')
@section('page-title', 'Customers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="text-muted">Manage customer contact information</span>
    <a href="{{ route('admin.customers.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-person-plus"></i> Add Customer
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($customers->count() > 0)
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th style="width: 5%">#</th>
                    <th style="width: 25%">Name</th>
                    <th style="width: 20%">Phone</th>
                    <th style="width: 25%">Email</th>
                    <th style="width: 10%">Status</th>
                    <th style="width: 15%" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                <tr>
                    <td class="text-muted">{{ ($customers->currentPage() - 1) * $customers->perPage() + $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $customer->name }}</td>
                    <td>
                        @if($customer->phone)
                            <a href="tel:{{ $customer->phone }}" class="text-decoration-none">{{ $customer->phone }}</a>
                        @else
                            <span class="text-muted small">—</span>
                        @endif
                    </td>
                    <td>
                        @if($customer->email)
                            <a href="mailto:{{ $customer->email }}" class="text-decoration-none">{{ $customer->email }}</a>
                        @else
                            <span class="text-muted small">—</span>
                        @endif
                    </td>
                    <td>
                        @if($customer->is_active)
                            <span class="badge bg-success-soft text-success">Active</span>
                        @else
                            <span class="badge bg-secondary-soft text-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-sm btn-outline-secondary" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.customers.destroy', $customer) }}" class="d-inline"
                              onsubmit="return confirm('Deactivate this customer?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Deactivate">
                                <i class="bi bi-eye-slash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="bi bi-inbox"></i> No customers yet. 
                        <a href="{{ route('admin.customers.create') }}">Add one</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        @if($customers->hasPages())
        <div class="d-flex justify-content-center p-3">
            {{ $customers->links() }}
        </div>
        @endif
        @else
        <div class="text-center text-muted py-5">
            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
            <p class="mt-2">No customers yet.</p>
            <a href="{{ route('admin.customers.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-person-plus"></i> Add First Customer
            </a>
        </div>
        @endif
    </div>
</div>
@endsection

<style>
.badge.bg-success-soft {
    background-color: rgba(25, 135, 84, 0.15);
}
.badge.bg-secondary-soft {
    background-color: rgba(108, 117, 125, 0.15);
}
</style>
