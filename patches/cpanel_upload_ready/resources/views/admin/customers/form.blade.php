@extends('layouts.app')
@section('title', isset($customer->id) ? 'Edit Customer' : 'Add Customer')
@section('page-title', isset($customer->id) ? 'Edit Customer' : 'Add Customer')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="POST" action="{{ isset($customer->id) ? route('admin.customers.update', $customer) : route('admin.customers.store') }}">
                    @csrf
                    @if(isset($customer->id))
                        @method('PUT')
                    @endif

                    {{-- Customer Name --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" 
                               value="{{ old('name', $customer->name ?? '') }}"
                               class="form-control @error('name') is-invalid @enderror"
                               placeholder="John Doe" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Phone --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phone Number</label>
                        <input type="tel" name="phone" 
                               value="{{ old('phone', $customer->phone ?? '') }}"
                               class="form-control @error('phone') is-invalid @enderror"
                               placeholder="+233 24 123 4567">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input type="email" name="email" 
                               value="{{ old('email', $customer->email ?? '') }}"
                               class="form-control @error('email') is-invalid @enderror"
                               placeholder="john@example.com">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Notes --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea name="notes" 
                                  class="form-control @error('notes') is-invalid @enderror"
                                  placeholder="E.g., VIP customer, prefers cash, allergic to..."
                                  rows="3">{{ old('notes', $customer->notes ?? '') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Active Status --}}
                    @if(isset($customer->id))
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive"
                                   {{ old('is_active', $customer->is_active ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="isActive">
                                Active Customer
                            </label>
                        </div>
                        <small class="text-muted">Uncheck to deactivate (hidden from autocomplete)</small>
                    </div>
                    @endif

                    {{-- Form Actions --}}
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-check-lg"></i> {{ isset($customer->id) ? 'Update' : 'Create' }} Customer
                        </button>
                        <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
