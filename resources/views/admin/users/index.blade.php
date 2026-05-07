@extends('layouts.app')
@section('title', 'Users')
@section('page-title', 'Users')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="text-muted">Owner and cashier accounts</span>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Add User
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Branch</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $user->name }}</td>
                    <td class="text-muted">{{ $user->email }}</td>
                    <td>
                        @if($user->role === 'superadmin')
                            <span class="badge bg-danger">Super Admin</span>
                        @elseif($user->role === 'owner')
                            <span class="badge bg-warning text-dark">Owner</span>
                        @else
                            <span class="badge bg-primary">Cashier</span>
                        @endif
                    </td>
                    <td>{{ $user->branch->name ?? '—' }}</td>
                    <td class="text-end">
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @if($user->id !== auth()->id())
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="d-inline"
                              onsubmit="return confirm('Delete this user?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No users yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
