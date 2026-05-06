@extends('layouts.app')
@section('title', isset($branch->id) ? 'Edit Branch' : 'Add Branch')
@section('page-title', isset($branch->id) ? 'Edit Branch' : 'Add Branch')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-6">
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ isset($branch->id) ? route('admin.branches.update', $branch) : route('admin.branches.store') }}">
            @csrf
            @if(isset($branch->id)) @method('PUT') @endif

            <div class="mb-3">
                <label class="form-label fw-semibold">Branch Name <span class="text-danger">*</span></label>
                <input type="text" name="name" value="{{ old('name', $branch->name) }}"
                       class="form-control @error('name') is-invalid @enderror"
                       placeholder="e.g. Baidoos Barbing - Main">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Business Category <span class="text-danger">*</span></label>
                <select name="business_type_id" class="form-select @error('business_type_id') is-invalid @enderror">
                    <option value="">— Select —</option>
                    @foreach($businessTypes as $bt)
                        <option value="{{ $bt->id }}"
                            {{ old('business_type_id', $branch->business_type_id) == $bt->id ? 'selected' : '' }}>
                            {{ $bt->name }}
                        </option>
                    @endforeach
                </select>
                @error('business_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label fw-semibold">Address</label>
                    <input type="text" name="address" value="{{ old('address', $branch->address) }}"
                           class="form-control" placeholder="Street address">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $branch->phone) }}"
                           class="form-control" placeholder="050-000-0000">
                </div>
            </div>

            <div class="mb-4 form-check">
                <input type="checkbox" class="form-check-input" name="is_active" id="is_active" value="1"
                    {{ old('is_active', $branch->is_active ?? true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Active</label>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> {{ isset($branch->id) ? 'Update' : 'Create' }}
                </button>
                <a href="{{ route('admin.branches.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
@endsection
