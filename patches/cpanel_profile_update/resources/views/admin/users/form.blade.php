@extends('layouts.app')
@section('title', isset($user->id) ? 'Edit User' : 'Add User')
@section('page-title', isset($user->id) ? 'Edit User' : 'Add User')

@section('content')
@php($canManageRoles = auth()->user()->isSuperAdmin())
@php($selectedRole = old('role', $user->role))
<div class="row justify-content-center">
<div class="col-lg-6">
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ isset($user->id) ? route('admin.users.update', $user) : route('admin.users.store') }}">
            @csrf
            @if(isset($user->id)) @method('PUT') @endif

            <div class="mb-3">
                <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}"
                       class="form-control @error('name') is-invalid @enderror" placeholder="Full name">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}"
                       class="form-control @error('email') is-invalid @enderror">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Phone <small class="text-muted">(used for SMS transaction alerts)</small></label>
                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                       class="form-control @error('phone') is-invalid @enderror" placeholder="e.g. 0244123456">
                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Password {{ isset($user->id) ? '(leave blank to keep)' : '' }} <span class="text-danger">*</span></label>
                    <input type="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           {{ isset($user->id) ? '' : 'required' }}>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="form-control">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Role @if(!$canManageRoles)<span class="text-danger">*</span>@endif</label>
                    @if(isset($user->id) && !$canManageRoles)
                        <input type="text" class="form-control bg-light" value="{{ $selectedRole === 'superadmin' ? 'Super Admin' : ucfirst($selectedRole) }}" disabled>
                        <input type="hidden" name="role" value="{{ $selectedRole }}">
                    @else
                    <select name="role" id="roleSelect" class="form-select @error('role') is-invalid @enderror"
                            onchange="toggleBranch()">
                        <option value="owner" {{ $selectedRole === 'owner' ? 'selected' : '' }}>Owner</option>
                        <option value="cashier" {{ $selectedRole === 'cashier' ? 'selected' : '' }}>Cashier</option>
                        @if($canManageRoles)
                        <option value="superadmin" {{ $selectedRole === 'superadmin' ? 'selected' : '' }}>Super Admin</option>
                        @endif
                    </select>
                    @endif
                    @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3" id="branchField">
                    <label class="form-label fw-semibold">Assign to Branch @if(!auth()->user()->isSuperAdmin())<span class="text-danger">*</span>@endif</label>
                    <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                        <option value="">— Select Branch —</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}"
                                {{ old('branch_id', $user->branch_id) == $b->id ? 'selected' : '' }}>
                                {{ $b->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="d-flex gap-2 mt-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> {{ isset($user->id) ? 'Update' : 'Create' }}
                </button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>

@push('scripts')
<script>
function currentRole() {
    const roleSelect = document.getElementById('roleSelect');
    if (roleSelect) {
        return roleSelect.value;
    }

    const hiddenRole = document.querySelector('input[name="role"][type="hidden"]');
    return hiddenRole ? hiddenRole.value : 'owner';
}

function toggleBranch() {
    const role = currentRole();
    document.getElementById('branchField').style.display = role === 'owner' ? 'none' : '';
}

const roleSelect = document.getElementById('roleSelect');
if (roleSelect) {
    roleSelect.addEventListener('change', toggleBranch);
}
toggleBranch();
</script>
@endpush
@endsection
