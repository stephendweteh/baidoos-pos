@extends('layouts.app')
@section('title', isset($item->id) ? 'Edit Item' : 'Add Item')
@section('page-title', isset($item->id) ? 'Edit Item' : 'Add Item')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-5">
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ isset($item->id) ? route('admin.items.update', $item) : route('admin.items.store') }}">
            @csrf
            @if(isset($item->id)) @method('PUT') @endif

            <div class="mb-3">
                <label class="form-label fw-semibold">Branch <span class="text-danger">*</span></label>
                <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                    <option value="">— Select Branch —</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}"
                            {{ old('branch_id', $item->branch_id) == $b->id ? 'selected' : '' }}>
                            {{ $b->name }}
                        </option>
                    @endforeach
                </select>
                @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Item / Service Name <span class="text-danger">*</span></label>
                <input type="text" name="name" value="{{ old('name', $item->name) }}"
                       class="form-control @error('name') is-invalid @enderror"
                       placeholder="e.g. Haircut, Beer, Massage">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Price (GH₵) <span class="text-danger">*</span></label>
                    <input type="number" name="price" value="{{ old('price', $item->price) }}"
                           class="form-control @error('price') is-invalid @enderror"
                           step="0.01" min="0" placeholder="0.00">
                    @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                    <select name="type" id="typeSelect" class="form-select @error('type') is-invalid @enderror" onchange="toggleItemTypeFields()">
                        <option value="service" {{ old('type', $item->type) === 'service' ? 'selected' : '' }}>Service</option>
                        <option value="product" {{ old('type', $item->type) === 'product' ? 'selected' : '' }}>Product</option>
                    </select>
                </div>
            </div>

            <div class="mb-3" id="stockQuantityWrap">
                <label class="form-label fw-semibold">Stock Quantity <span class="text-danger">*</span></label>
                <input type="number" name="stock_quantity" id="stock_quantity"
                       value="{{ old('stock_quantity', $item->stock_quantity ?? 0) }}"
                       class="form-control @error('stock_quantity') is-invalid @enderror"
                       min="0" step="1" placeholder="0">
                <div class="form-text">Set current units available for sale.</div>
                @error('stock_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3 form-check form-switch" id="assignStaffWrap">
                <input type="checkbox" class="form-check-input" name="assign_staff" id="assign_staff" value="1"
                    {{ old('assign_staff', $item->assign_staff ?? false) ? 'checked' : '' }}>
                <label class="form-check-label" for="assign_staff">Assign this service to a staff member during sale</label>
                <div class="form-text">When enabled, the POS will show all staff in the selected branch for this service.</div>
            </div>

            <div class="mb-4 form-check">
                <input type="checkbox" class="form-check-input" name="is_active" id="is_active" value="1"
                    {{ old('is_active', $item->is_active ?? true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Active (visible on POS)</label>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> {{ isset($item->id) ? 'Update' : 'Create' }}
                </button>
                <a href="{{ route('admin.items.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>

@push('scripts')
<script>
function toggleItemTypeFields() {
    const isService = document.getElementById('typeSelect').value === 'service';
    const assignStaffWrap = document.getElementById('assignStaffWrap');
    const assignStaffInput = document.getElementById('assign_staff');
    const stockWrap = document.getElementById('stockQuantityWrap');
    const stockInput = document.getElementById('stock_quantity');

    assignStaffWrap.style.display = isService ? '' : 'none';
    stockWrap.style.display = isService ? 'none' : '';
    stockInput.required = !isService;

    if (!isService) {
        assignStaffInput.checked = false;
    }
}

toggleItemTypeFields();
</script>
@endpush
@endsection
