@extends('layouts.app')
@section('title', 'POS — New Sale')
@section('page-title', 'New Sale — ' . ($branch->name ?? ''))

@php
    $staffOptions = $branchStaff->map(fn ($staff) => [
        'id' => $staff->id,
        'name' => $staff->name,
    ])->values();
@endphp

@push('styles')
<style>
    /* Hide sidebar and collapse main-wrap on POS sale page */
    body.pos-mode .sidebar { transform: translateX(-230px); }
    body.pos-mode .main-wrap { margin-left: 0 !important; }
    .sidebar { transition: transform .25s ease; }
    .main-wrap { transition: margin-left .25s ease; }
    /* Sidebar toggle button lives inside the topbar */
    #sidebarToggleBtn {
        width: 34px;
        height: 34px;
        border-radius: 6px;
        background: #1a2236;
        color: #fff;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        cursor: pointer;
        flex-shrink: 0;
    }
    #sidebarToggleBtn:hover { background: #2d3a55; }
    /* Push toggle into topbar; hide by default, show in pos-mode */
    #posTopbarBtn { display: none; }
    body.pos-mode #posTopbarBtn { display: flex; }
</style>
@endpush

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
                             onclick="addToCart({{ $item->id }}, '{{ addslashes($item->name) }}', {{ $item->price }}, '{{ $item->type }}', {{ (int) ($item->stock_quantity ?? 0) }}, {{ $item->assign_staff ? 'true' : 'false' }})">
                            <div class="fw-semibold" style="font-size:.9rem">{{ $item->name }}</div>
                            <div class="text-success fw-bold mt-1">GH₵ {{ number_format($item->price, 2) }}</div>
                            <div class="mt-1">
                                @if($item->type === 'service')
                                    <span class="badge badge-service" style="font-size:.65rem">Service</span>
                                    @if($item->assign_staff)
                                        <span class="badge bg-info-subtle text-info-emphasis" style="font-size:.65rem">Assign Staff</span>
                                    @endif
                                @else
                                    <span class="badge badge-product" style="font-size:.65rem">Product</span>
                                    @if(($item->stock_quantity ?? 0) > 0)
                                        <span class="badge bg-light text-dark" style="font-size:.65rem">Stock: {{ $item->stock_quantity }}</span>
                                    @else
                                        <span class="badge bg-danger" style="font-size:.65rem">Out of stock</span>
                                    @endif
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
                    <div class="border rounded p-2 mb-2 bg-light-subtle">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <small class="fw-semibold text-uppercase text-muted">Custom Line Item</small>
                            <small class="text-muted">For ad-hoc items or services</small>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-5">
                                <input type="text" id="customName" class="form-control form-control-sm" placeholder="Name">
                            </div>
                            <div class="col-md-3">
                                <select id="customType" class="form-select form-select-sm">
                                    <option value="service">Service</option>
                                    <option value="product">Product</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="number" id="customPrice" class="form-control form-control-sm" min="0" step="0.01" placeholder="Price">
                            </div>
                            <div class="col-md-2">
                                <input type="number" id="customQty" class="form-control form-control-sm" min="1" step="1" value="1" placeholder="Qty">
                            </div>
                            <div class="col-md-8">
                                <input type="text" id="customVariation" class="form-control form-control-sm" placeholder="Variation (optional, e.g. Premium)">
                            </div>
                            <div class="col-md-4 d-grid">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addCustomToCart()">
                                    <i class="bi bi-plus-circle"></i> Add Custom
                                </button>
                            </div>
                        </div>
                    </div>

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
                    {{-- Customer Info with Autocomplete --}}
                    <div class="mb-2 position-relative">
                        <input type="hidden" id="customerId" name="customer_id" value="">
                        <input type="text" id="customerSearch" name="customer_name" class="form-control form-control-sm @error('customer_name') is-invalid @enderror"
                               placeholder="Customer name * (start typing to find existing customer)" required 
                               value="{{ old('customer_name') }}" autocomplete="off">
                        <div id="customerSuggestions" class="position-absolute w-100 bg-white border rounded shadow-sm" 
                             style="display:none; top:100%; z-index:1000; max-height:150px; overflow-y:auto">
                        </div>
                        @error('customer_name')
                            <div class="invalid-feedback" style="font-size:.75rem">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-2">
                        <input type="tel" id="customerPhoneInput" name="customer_phone" class="form-control form-control-sm @error('customer_phone') is-invalid @enderror"
                               placeholder="Phone (optional — for SMS receipt / required for MTN MoMo)" value="{{ old('customer_phone') }}">
                        @error('customer_phone')
                            <div class="invalid-feedback" style="font-size:.75rem">{{ $message }}</div>
                        @enderror
                        <small id="momoPhoneHint" class="text-muted" style="font-size:.72rem; display:none">
                            Customer phone is required when MTN MoMo is selected.
                        </small>
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
                    <div class="btn-group w-100 mb-1" role="group">
                        @foreach(['cash' => 'Cash', 'mtn_momo' => 'MTN MoMo'] as $val => $label)
                        <input type="radio" class="btn-check payment-method-input" name="payment_method"
                               id="pm_{{ $val }}" value="{{ $val }}" {{ $val === 'cash' ? 'checked' : '' }}>
                        <label class="btn btn-outline-secondary btn-sm" for="pm_{{ $val }}">{{ $label }}</label>
                        @endforeach
                    </div>
                    <div id="momoHelp" class="alert alert-info py-1 px-2 mb-3" style="font-size:.75rem; display:none">
                        When you click Record Sale, customer will receive an MTN prompt on phone to enter PIN.
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
let customCounter = 0;
const branchStaff = @json($staffOptions);

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function addCustomToCart() {
    const name = document.getElementById('customName').value.trim();
    const type = document.getElementById('customType').value;
    const price = parseFloat(document.getElementById('customPrice').value);
    const qty = parseInt(document.getElementById('customQty').value || '1', 10);
    const variation = document.getElementById('customVariation').value.trim();

    if (!name) {
        window.alert('Enter a custom item name.');
        return;
    }

    if (!['service', 'product'].includes(type)) {
        window.alert('Select a valid custom item type.');
        return;
    }

    if (isNaN(price) || price < 0) {
        window.alert('Enter a valid custom item price.');
        return;
    }

    if (isNaN(qty) || qty < 1) {
        window.alert('Enter a valid custom quantity.');
        return;
    }

    customCounter += 1;
    const key = `custom-${customCounter}`;
    cart[key] = {
        id: null,
        key,
        name,
        price,
        qty,
        type,
        variation,
        isCustom: true,
        assignStaff: false,
        stockQuantity: null,
        staffId: ''
    };

    document.getElementById('customName').value = '';
    document.getElementById('customPrice').value = '';
    document.getElementById('customQty').value = '1';
    document.getElementById('customVariation').value = '';
    renderCart();
}

function addToCart(id, name, price, type, stockQuantity, assignStaff) {
    const key = String(id);

    if (type === 'product' && stockQuantity <= 0) {
        window.alert('This product is out of stock.');
        return;
    }

    if (cart[key]) {
        if (cart[key].type === 'product' && cart[key].qty >= cart[key].stockQuantity) {
            window.alert('Cannot add more than available stock.');
            return;
        }

        cart[key].qty++;
    } else {
        cart[key] = {
            id,
            key,
            name,
            price,
            qty: 1,
            type,
            variation: '',
            stockQuantity,
            assignStaff,
            isCustom: false,
            staffId: ''
        };
    }

    document.getElementById('tile-' + id).classList.add('in-cart');
    renderCart();
}

function updateQty(id, qty) {
    qty = parseInt(qty);
    if (isNaN(qty) || qty < 1) { removeFromCart(id); return; }

    if (cart[id].type === 'product' && cart[id].stockQuantity !== null && qty > cart[id].stockQuantity) {
        window.alert('Quantity cannot be greater than available stock.');
        qty = cart[id].stockQuantity;
    }

    cart[id].qty = qty;
    renderCart();
}

function updateVariation(id, value) {
    if (!cart[id]) {
        return;
    }

    cart[id].variation = value;
    syncHiddenInputs();
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

function updateStaff(id, staffId) {
    if (!cart[id]) {
        return;
    }

    cart[id].staffId = staffId;
    syncHiddenInputs();
}

function getStaffOptions(selectedId) {
    const baseOption = '<option value="">Select staff</option>';
    return baseOption + branchStaff.map(staff => {
        const selected = String(selectedId) === String(staff.id) ? 'selected' : '';
        return `<option value="${staff.id}" ${selected}>${staff.name}</option>`;
    }).join('');
}

function syncHiddenInputs() {
    const hidden = document.getElementById('hiddenInputs');
    let inputs = '';
    let idx = 0;

    Object.keys(cart).forEach(key => {
        const item = cart[key];

        if (item.isCustom) {
            inputs += `<input type="hidden" name="items[${idx}][is_custom]" value="1">`;
            inputs += `<input type="hidden" name="items[${idx}][custom_name]" value="${escapeHtml(item.name)}">`;
            inputs += `<input type="hidden" name="items[${idx}][type]" value="${item.type}">`;
            inputs += `<input type="hidden" name="items[${idx}][price]" value="${item.price}">`;
        } else {
            inputs += `<input type="hidden" name="items[${idx}][id]" value="${item.id}">`;
        }

        inputs += `<input type="hidden" name="items[${idx}][qty]" value="${item.qty}">`;
        inputs += `<input type="hidden" name="items[${idx}][variation]" value="${escapeHtml(item.variation || '')}">`;

        if (item.assignStaff) {
            inputs += `<input type="hidden" name="items[${idx}][staff_id]" value="${item.staffId}">`;
        }

        idx++;
    });

    hidden.innerHTML = inputs;
}

function renderCart() {
    const body   = document.getElementById('cartBody');
    const keys   = Object.keys(cart);
    const empty  = document.getElementById('cartEmpty');
    const table  = document.getElementById('cartTable');
    const btn    = document.getElementById('submitBtn');

    if (keys.length === 0) {
        empty.style.display = '';
        table.style.display = 'none';
        btn.disabled = true;
        document.getElementById('hiddenInputs').innerHTML = '';
        updateTotal(0);
        return;
    }

    empty.style.display = 'none';
    table.style.display = '';
    btn.disabled = false;

    let subtotal = 0;
    let rows     = '';

    keys.forEach(id => {
        const item = cart[id];
        const safeId = escapeHtml(id);
        const lineTotal = item.price * item.qty;
        subtotal += lineTotal;

        const stockHint = item.type === 'product' && item.stockQuantity !== null
            ? `<div><small class="text-muted">In stock: ${item.stockQuantity}</small></div>`
            : '';

        const variationInput = item.type === 'service'
            ? `<div class="mt-2"><input type="text" class="form-control form-control-sm" value="${escapeHtml(item.variation || '')}" placeholder="Variation (optional)" onchange="updateVariation('${safeId}', this.value)"></div>`
            : '';

        const staffSelector = item.assignStaff
            ? `<div class="mt-2"><select class="form-select form-select-sm" onchange="updateStaff('${safeId}', this.value)" required>
                    ${getStaffOptions(item.staffId)}
               </select></div>`
            : '';

        const customBadge = item.isCustom
            ? '<span class="badge bg-secondary-subtle text-secondary-emphasis">Custom</span>'
            : '';

        rows += `<tr>
            <td style="font-size:.8rem">${escapeHtml(item.name)} ${customBadge}<br><small class="text-muted">GH₵${item.price.toFixed(2)}</small>${stockHint}${variationInput}${staffSelector}</td>
            <td><input type="number" class="form-control form-control-sm" min="1" ${item.type === 'product' && item.stockQuantity !== null ? `max="${item.stockQuantity}"` : ''} value="${item.qty}"
                       onchange="updateQty('${safeId}', this.value)" style="width:60px"></td>
            <td class="text-end fw-semibold" style="font-size:.85rem">GH₵ ${lineTotal.toFixed(2)}</td>
            <td><button type="button" class="btn btn-sm text-danger p-0 ps-1" onclick="removeFromCart('${safeId}')">
                <i class="bi bi-x-circle"></i></button></td>
        </tr>`;
    });

    body.innerHTML = rows;
    syncHiddenInputs();
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

function syncMomoRequirements() {
    const selected = document.querySelector('input[name="payment_method"]:checked');
    const isMomo = selected && selected.value === 'mtn_momo';
    const phoneInput = document.getElementById('customerPhoneInput');
    const momoHelp = document.getElementById('momoHelp');
    const momoPhoneHint = document.getElementById('momoPhoneHint');

    phoneInput.required = !!isMomo;
    momoHelp.style.display = isMomo ? '' : 'none';
    momoPhoneHint.style.display = isMomo ? '' : 'none';
}

document.querySelectorAll('.payment-method-input').forEach(el => {
    el.addEventListener('change', syncMomoRequirements);
});

syncMomoRequirements();

// ─── CUSTOMER AUTOCOMPLETE ───
const customerSearch = document.getElementById('customerSearch');
const customerSuggestions = document.getElementById('customerSuggestions');
const customerPhoneInput = document.getElementById('customerPhoneInput');
const customerEmailInput = document.querySelector('input[name="customer_email"]');
const customerIdInput = document.getElementById('customerId');
let autocompleteTimeout;

customerSearch.addEventListener('input', function () {
    const query = this.value.trim();
    clearTimeout(autocompleteTimeout);

    if (query.length < 2) {
        customerSuggestions.style.display = 'none';
        customerIdInput.value = '';
        return;
    }

    // Debounce API call
    autocompleteTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`/api/customers/search?q=${encodeURIComponent(query)}`);
            const customers = await response.json();

            if (!customers.length) {
                customerSuggestions.innerHTML = '<div class="px-3 py-2 text-muted" style="font-size:.85rem">No customers found</div>';
                customerSuggestions.style.display = 'block';
                customerIdInput.value = '';
                return;
            }

            customerSuggestions.innerHTML = customers.map(c => `
                <div class="px-3 py-2 border-bottom" style="cursor:pointer; font-size:.85rem" 
                     onclick="selectCustomer(${c.id}, '${escapeHtml(c.name)}', '${escapeHtml(c.phone)}', '${escapeHtml(c.email)}')">
                    <div class="fw-semibold">${escapeHtml(c.name)}</div>
                    <small class="text-muted">${c.phone ? '📞 ' + escapeHtml(c.phone) : ''} ${c.email ? '✉️ ' + escapeHtml(c.email) : ''}</small>
                </div>
            `).join('');

            customerSuggestions.style.display = 'block';
        } catch (error) {
            console.error('Customer search error:', error);
        }
    }, 300);
});

function selectCustomer(id, name, phone, email) {
    customerIdInput.value = id;
    customerSearch.value = name;
    customerPhoneInput.value = phone;
    customerEmailInput.value = email;
    customerSuggestions.style.display = 'none';
}

// Hide suggestions when clicking outside
document.addEventListener('click', function (e) {
    if (e.target !== customerSearch && !customerSuggestions.contains(e.target)) {
        customerSuggestions.style.display = 'none';
    }
});

// Handle new customers (when autocomplete is not used)
customerSearch.addEventListener('blur', function () {
    if (!customerIdInput.value && this.value.trim()) {
        // User typed a name but didn't select from suggestions - treat as new customer
        customerIdInput.value = '';
    }
});

// Prevent double-submit
document.getElementById('saleForm').addEventListener('submit', function (event) {
    const missingAssignment = Object.values(cart).some(item => item.assignStaff && !item.staffId);
    if (missingAssignment) {
        event.preventDefault();
        window.alert('Assign a staff member to each selected service before recording the sale.');
        document.getElementById('submitBtn').disabled = false;
        return;
    }

    const selected = document.querySelector('input[name="payment_method"]:checked');
    const isMomo = selected && selected.value === 'mtn_momo';
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitBtn').textContent = isMomo ? 'Sending MTN Prompt...' : 'Recording...';
});

// ── Sidebar toggle on POS page ────────────────────────────────
(function () {
    // Create toggle button and inject into topbar placeholder
    var btn = document.createElement('button');
    btn.id = 'sidebarToggleBtn';
    btn.title = 'Show / hide menu';
    btn.innerHTML = '<i class="bi bi-list"></i>';
    var placeholder = document.getElementById('posTopbarBtn');
    if (placeholder) placeholder.appendChild(btn);

    // Start with sidebar hidden
    document.body.classList.add('pos-mode');

    btn.addEventListener('click', function () {
        document.body.classList.toggle('pos-mode');
        var icon = btn.querySelector('i');
        icon.className = document.body.classList.contains('pos-mode')
            ? 'bi bi-list'
            : 'bi bi-x-lg';
    });
})();
</script>
@endpush
