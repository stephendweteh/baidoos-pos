@extends('layouts.app')
@section('title', 'Branches')
@section('page-title', 'Branches')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="text-muted">Manage your business locations</span>
    <a href="{{ route('admin.branches.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Add Branch
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Branch</th>
                    <th>Category</th>
                    <th>Phone</th>
                    <th>Users</th>
                    <th>Items</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($branches as $branch)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        <div class="fw-semibold">{{ $branch->name }}</div>
                        <small class="text-muted">{{ $branch->address }}</small>
                    </td>
                    <td><span class="badge bg-info text-dark">{{ $branch->businessType->name }}</span></td>
                    <td>{{ $branch->phone ?? '—' }}</td>
                    <td>{{ $branch->users_count }}</td>
                    <td>{{ $branch->items_count }}</td>
                    <td>
                        @if($branch->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.branches.edit', $branch) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.branches.destroy', $branch) }}" class="d-inline"
                              onsubmit="return confirm('Delete this branch?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No branches yet. Add one.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
