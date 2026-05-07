@extends('layouts.app')
@section('title', isset($user->id) ? 'Edit User' : 'Add User')
@section('page-title', isset($user->id) ? 'Edit User' : 'Add User')

@section('content')
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
                    <label class="form-label fw-semibold">Role @if(!auth()->user()->isSuperAdmin())<span class="text-danger">*</span>@endif</label>
                    @if(isset($user->id) && $user->role === 'superadmin')
                        {{-- Superadmin role cannot be changed via this form --}}
                        <input type="text" class="form-control bg-light text-capitalize" value="Super Admin" disabled>
                        <input type="hidden" name="role" value="superadmin">
                    @else
                    <select name="role" id="roleSelect" class="form-select @error('role') is-invalid @enderror"
                            onchange="toggleBranch()">
                        @if(!auth()->user()->isSuperAdmin())
                        <option value="cashier" {{ old('role', $user->role) === 'cashier' ? 'selected' : '' }}>Cashier</option>
                        @endif
                        <option value="owner" {{ old('role', $user->role) === 'owner' ? 'selected' : '' }}>Owner</option>
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
function toggleBranch() {
    const role = document.getElementById('roleSelect').value;
    document.getElementById('branchField').style.display = role === 'owner' ? 'none' : '';
}
toggleBranch();
</script>
@endpush
@endsection
