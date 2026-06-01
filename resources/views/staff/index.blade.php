@extends('layouts.app')
@section('title', 'Service Staff')
@section('page-title', 'Service Staff')

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Add Staff</div>
            <div class="card-body">
                <form method="POST" action="{{ route('staff.store') }}">
                    @csrf

                    @if(auth()->user()->isOwner() || auth()->user()->isSuperAdmin())
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Branch <span class="text-danger">*</span></label>
                        <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                            <option value="">Select branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ old('branch_id', $branchId) == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Staff Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" placeholder="e.g. Kojo, Ama, Rashid">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" placeholder="e.g. kojo@example.com">
                        <div class="form-text">Optional. Used for service assignment alerts.</div>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Add Staff
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted">Create branch staff by name for service assignment.</span>
            @if(auth()->user()->isOwner() || auth()->user()->isSuperAdmin())
            <form method="GET" action="{{ route('staff.index') }}" class="d-flex gap-2">
                <select name="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </form>
            @endif
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>#</th><th>Name</th><th>Email</th><th>Branch</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse($staff as $member)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <form method="POST" action="{{ route('staff.update', $member) }}" class="d-flex gap-2 align-items-center">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="is_active" value="{{ $member->is_active ? 1 : 0 }}">
                                    <input type="text" name="name" value="{{ $member->name }}" class="form-control form-control-sm" style="max-width:220px">
                                    <input type="email" name="email" value="{{ $member->email }}" class="form-control form-control-sm" style="max-width:240px" placeholder="Email (optional)">
                                    <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="bi bi-check-lg"></i></button>
                                </form>
                            </td>
                            <td class="text-muted">{{ $member->email ?: '—' }}</td>
                            <td>{{ $member->branch->name ?? '—' }}</td>
                            <td>
                                <form method="POST" action="{{ route('staff.update', $member) }}">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="name" value="{{ $member->name }}">
                                    <input type="hidden" name="email" value="{{ $member->email }}">
                                    <input type="hidden" name="is_active" value="0">
                                    <div class="form-check form-switch mb-0">
                                        <input type="checkbox" class="form-check-input" name="is_active" value="1" {{ $member->is_active ? 'checked' : '' }} onchange="this.form.submit()">
                                        <label class="form-check-label small">{{ $member->is_active ? 'Active' : 'Inactive' }}</label>
                                    </div>
                                </form>
                            </td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('staff.destroy', $member) }}" class="d-inline" onsubmit="return confirm('Remove this staff name?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No staff names added yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection