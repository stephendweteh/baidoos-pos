@extends('layouts.app')
@section('title', 'POS — New Sale')
@section('page-title', 'New Sale — ' . ($branch->name ?? ''))

@section('content')
@if($todayClosed)
<div class="alert alert-warning">
    <i class="bi bi-lock-fill"></i>
    <strong>Today is closed.</strong> No new sales can be recorded for this branch today.
    <a href="{{ route('day-closing.index') }}" class="alert-link ms-2">View Day Closing Report</a>
</div>
@else

<div class="row g-3">
    {{-- ─── LEFT: Item Grid ───────────────────────────────── --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2 d-flex align-items-center gap-2">
                <i class="bi bi-grid text-primary"></i>
                <strong>Select Items / Services</strong>
                <input type="text" id="itemSearch" class="form-control form-control-sm ms-auto" style="max-width:200px"
                       placeholder="Search..." onkeyup="filterItems()">
            </div>
            <div class="card-body">
                @if($items->isEmpty())
                    <p class="text-muted text-center py-3">
                        No active items for this branch.
                        <a href="{{ route('admin.items.create') }}">Add items</a>
                    </p>
                @else
                <div class="row g-2" id="itemGrid">
                    @foreach($items->sortBy('name') as $item)
                    <div class="col-6 col-md-4 item-tile" data-name="{{ strtolower($item->name) }}">
                        <div class="btn-pos" id="tile-{{ $item->id }}"
                             onclick="addToCart({{ $item->id }}, '{{ addslashes($item->name) }}', {{ $item->price }})">
                            <div class="fw-semibold" style="font-size:.9rem">{{ $item->name }}</div>
                            <div class="text-success fw-bold mt-1">GH₵ {{ number_format($item->price, 2) }}</div>
                            <div class="mt-1">
                                @if($item->type === 'service')
                                    <span class="badge badge-service" style="font-size:.65rem">Service</span>
                                @else
                                    <span class="badge badge-product" style="font-size:.65rem">Product</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ─── RIGHT: Cart + Checkout ─────────────────────────── --}}
    <div class="col-lg-5">
        <form method="POST" action="{{ route('pos.sale.store') }}" id="saleForm">
            @csrf

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-2">
                    <strong><i class="bi bi-cart3 text-primary"></i> Cart</strong>
                    <button type="button" class="btn btn-sm btn-outline-danger float-end" onclick="clearCart()">
                        <i class="bi bi-trash"></i> Clear
                    </button>
                </div>
                <div class="card-body p-2">
                    <div id="cartEmpty" class="text-center text-muted py-3" style="font-size:.85rem">
                        <i class="bi bi-cart-x" style="font-size:2rem"></i><br>No items added yet
                    </div>
                    <table class="table table-sm mb-0" id="cartTable" style="display:none">
                        <thead><tr><th>Item</th><th style="width:80px">Qty</th><th class="text-end">Total</th><th></th></tr></thead>
                        <tbody id="cartBody"></tbody>
                        <tfoot>
                            <tr><td colspan="2" class="text-end text-muted">Subtotal</td>
                                <td class="text-end fw-semibold" id="subtotalDisplay">GH₵ 0.00</td><td></td></tr>
                        </tfoot>
                    </table>
                </div>

                <div class="card-footer bg-white">
                    {{-- Customer Info --}}
                    <div class="mb-2">
                        <input type="text" name="customer_name" class="form-control form-control-sm @error('customer_name') is-invalid @enderror"
                               placeholder="Customer name *" required value="{{ old('customer_name') }}">
                        @error('customer_name')
                            <div class="invalid-feedback" style="font-size:.75rem">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-2">
                        <input type="tel" name="customer_phone" class="form-control form-control-sm @error('customer_phone') is-invalid @enderror"
                               placeholder="Phone (optional — for SMS receipt)" value="{{ old('customer_phone') }}">
                        @error('customer_phone')
                            <div class="invalid-feedback" style="font-size:.75rem">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-2">
                        <input type="email" name="customer_email" class="form-control form-control-sm @error('customer_email') is-invalid @enderror"
                               placeholder="Email (optional)" value="{{ old('customer_email') }}">
                        @error('customer_email')
                            <div class="invalid-feedback" style="font-size:.75rem">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Discount --}}
                    <div class="input-group input-group-sm mb-2">
                        <span class="input-group-text">Discount GH₵</span>
                        <input type="number" name="discount" id="discountInput" class="form-control"
                               min="0" step="0.01" value="0" onchange="updateTotal()">
                    </div>

                    {{-- Total --}}
                    <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                        <span class="fw-bold">TOTAL</span>
                        <span class="fw-bold fs-5 text-primary" id="totalDisplay">GH₵ 0.00</span>
                    </div>

                    {{-- Payment Method --}}
                    <div class="btn-group w-100 mb-3" role="group">
                        @foreach(['cash' => 'Cash', 'transfer' => 'Transfer', 'card' => 'Card'] as $val => $label)
                        <input type="radio" class="btn-check" name="payment_method"
                               id="pm_{{ $val }}" value="{{ $val }}" {{ $val === 'cash' ? 'checked' : '' }}>
                        <label class="btn btn-outline-secondary btn-sm" for="pm_{{ $val }}">{{ $label }}</label>
                        @endforeach
                    </div>

                    {{-- Notes --}}
                    <textarea name="notes" class="form-control form-control-sm mb-3" rows="2"
                              placeholder="Notes (optional)"></textarea>

                    {{-- Hidden cart items injected by JS --}}
                    <div id="hiddenInputs"></div>

                    <button type="submit" id="submitBtn" class="btn btn-success w-100 fw-bold" disabled>
                        <i class="bi bi-check-circle"></i> Record Sale
                    </button>
                </div>
            </div>
        </form>

        {{-- Today's summary --}}
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white py-2">
                <small class="fw-semibold text-muted">TODAY'S SALES</small>
                <span class="float-end fw-bold text-success">GH₵ {{ number_format($todayTotal, 2) }}</span>
            </div>
            <div class="card-body p-0">
                <div style="max-height:200px; overflow-y:auto">
                    @forelse($todaySales as $s)
                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                        <div>
                            <div style="font-size:.8rem" class="fw-semibold">
                                #{{ $s->id }} {{ $s->customer_name ? '— '.$s->customer_name : '' }}
                            </div>
                            <small class="text-muted">{{ $s->payment_method }} · {{ $s->created_at->format('h:i A') }}</small>
                        </div>
                        <div class="fw-semibold text-success" style="font-size:.85rem">
                            GH₵ {{ number_format($s->total, 2) }}
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-3" style="font-size:.8rem">No sales yet today</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@endif
@endsection

@push('scripts')
<script>
let cart = {};

function addToCart(id, name, price) {
    if (cart[id]) {
        cart[id].qty++;
    } else {
        cart[id] = { id, name, price, qty: 1 };
    }
    document.getElementById('tile-' + id).classList.add('in-cart');
    renderCart();
}

function updateQty(id, qty) {
    qty = parseInt(qty);
    if (qty < 1) { removeFromCart(id); return; }
    cart[id].qty = qty;
    renderCart();
}

function removeFromCart(id) {
    delete cart[id];
    const tile = document.getElementById('tile-' + id);
    if (tile) tile.classList.remove('in-cart');
    renderCart();
}

function clearCart() {
    Object.keys(cart).forEach(id => {
        const tile = document.getElementById('tile-' + id);
        if (tile) tile.classList.remove('in-cart');
    });
    cart = {};
    renderCart();
}

function renderCart() {
    const body   = document.getElementById('cartBody');
    const keys   = Object.keys(cart);
    const empty  = document.getElementById('cartEmpty');
    const table  = document.getElementById('cartTable');
    const btn    = document.getElementById('submitBtn');
    const hidden = document.getElementById('hiddenInputs');

    if (keys.length === 0) {
        empty.style.display = '';
        table.style.display = 'none';
        btn.disabled = true;
        hidden.innerHTML = '';
        updateTotal(0);
        return;
    }

    empty.style.display = 'none';
    table.style.display = '';
    btn.disabled = false;

    let subtotal = 0;
    let rows     = '';
    let inputs   = '';
    let idx      = 0;

    keys.forEach(id => {
        const item = cart[id];
        const lineTotal = item.price * item.qty;
        subtotal += lineTotal;

        rows += `<tr>
            <td style="font-size:.8rem">${item.name}<br><small class="text-muted">GH₵${item.price.toFixed(2)}</small></td>
            <td><input type="number" class="form-control form-control-sm" min="1" value="${item.qty}"
                       onchange="updateQty(${id}, this.value)" style="width:60px"></td>
            <td class="text-end fw-semibold" style="font-size:.85rem">GH₵ ${lineTotal.toFixed(2)}</td>
            <td><button type="button" class="btn btn-sm text-danger p-0 ps-1" onclick="removeFromCart(${id})">
                <i class="bi bi-x-circle"></i></button></td>
        </tr>`;

        inputs += `<input type="hidden" name="items[${idx}][id]"  value="${id}">`;
        inputs += `<input type="hidden" name="items[${idx}][qty]" value="${item.qty}">`;
        idx++;
    });

    body.innerHTML = rows;
    hidden.innerHTML = inputs;
    document.getElementById('subtotalDisplay').textContent = 'GH₵ ' + subtotal.toFixed(2);
    updateTotal(subtotal);
}

function updateTotal(subtotal) {
    if (subtotal === undefined) {
        const keys = Object.keys(cart);
        subtotal = keys.reduce((s, id) => s + cart[id].price * cart[id].qty, 0);
    }
    const discount = parseFloat(document.getElementById('discountInput').value) || 0;
    const total    = Math.max(0, subtotal - discount);
    document.getElementById('totalDisplay').textContent = 'GH₵ ' + total.toFixed(2);
}

function filterItems() {
    const val   = document.getElementById('itemSearch').value.toLowerCase();
    const tiles = document.querySelectorAll('.item-tile');
    tiles.forEach(t => {
        t.style.display = t.dataset.name.includes(val) ? '' : 'none';
    });
}

// Prevent double-submit
document.getElementById('saleForm').addEventListener('submit', function () {
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitBtn').textContent = 'Recording...';
});
</script>
@endpush
