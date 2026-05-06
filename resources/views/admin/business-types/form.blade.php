@extends('layouts.app')
@section('title', isset($type->id) ? 'Edit Category' : 'Add Category')
@section('page-title', isset($type->id) ? 'Edit Category' : 'Add Category')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-6">
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ isset($type->id) ? route('admin.business-types.update', $type) : route('admin.business-types.store') }}">
            @csrf
            @if(isset($type->id)) @method('PUT') @endif

            <div class="mb-3">
                <label class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
                <input type="text" name="name" value="{{ old('name', $type->name) }}"
                       class="form-control @error('name') is-invalid @enderror"
                       placeholder="e.g. Barbing Salon">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Description</label>
                <input type="text" name="description" value="{{ old('description', $type->description) }}"
                       class="form-control" placeholder="Brief description (optional)">
            </div>

            <div class="mb-4 form-check">
                <input type="checkbox" class="form-check-input" name="is_active" id="is_active" value="1"
                    {{ old('is_active', $type->is_active ?? true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Active</label>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> {{ isset($type->id) ? 'Update' : 'Create' }}
                </button>
                <a href="{{ route('admin.business-types.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
@endsection
