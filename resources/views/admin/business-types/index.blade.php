@extends('layouts.app')
@section('title', 'Business Categories')
@section('page-title', 'Business Categories')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="text-muted">Manage business types (categories)</span>
    <a href="{{ route('admin.business-types.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Add Category
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Branches</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($types as $type)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $type->name }}</td>
                    <td class="text-muted">{{ $type->description ?? '—' }}</td>
                    <td><span class="badge bg-light text-dark">{{ $type->branches_count }}</span></td>
                    <td>
                        @if($type->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.business-types.edit', $type) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.business-types.destroy', $type) }}" class="d-inline"
                              onsubmit="return confirm('Delete this category?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No categories yet. Add one.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
