@extends('layouts.app')
@section('title', 'Items & Services')
@section('page-title', 'Items & Services')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <form method="GET" class="d-flex gap-2">
        <select name="branch_id" class="form-select form-select-sm" style="width:200px" onchange="this.form.submit()">
            <option value="">All Branches</option>
            @foreach($branches as $b)
                <option value="{{ $b->id }}" {{ $branchFilter == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
        </select>
    </form>
    <a href="{{ route('admin.items.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Add Item
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Branch</th>
                    <th>Type</th>
                    <th>Stock</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $item->name }}</td>
                    <td class="text-muted">{{ $item->branch->name }}</td>
                    <td>
                        @if($item->type === 'service')
                            <span class="badge badge-service">Service</span>
                        @else
                            <span class="badge badge-product">Product</span>
                        @endif
                    </td>
                    <td>
                        @if($item->type === 'product')
                            <span class="fw-semibold">{{ $item->stock_quantity ?? 0 }}</span>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </td>
                    <td class="fw-semibold">GH₵ {{ number_format($item->price, 2) }}</td>
                    <td>
                        @if($item->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.items.edit', $item) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.items.destroy', $item) }}" class="d-inline"
                              onsubmit="return confirm('Deactivate this item?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-warning" title="Deactivate">
                                <i class="bi bi-eye-slash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No items yet. Add one.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
