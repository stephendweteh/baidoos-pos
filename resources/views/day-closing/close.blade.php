@extends('layouts.app')
@section('title', 'Close Day — ' . $branch->name)
@section('page-title', 'Close Day — ' . $branch->name)

@section('content')
<div class="row justify-content-center">
<div class="col-lg-6">

{{-- Summary Card --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-bar-chart text-primary"></i>
        Today's Trading Summary — {{ now()->format('d M Y') }}
    </div>
    <div class="card-body">
        <div class="row text-center g-3">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="text-muted" style="font-size:.7rem">TOTAL SALES</div>
                    <div class="fw-bold text-success fs-5">GH₵ {{ number_format($summary['total_sales'], 2) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="text-muted" style="font-size:.7rem">TRANSACTIONS</div>
                    <div class="fw-bold fs-5">{{ $summary['transaction_count'] }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="text-muted" style="font-size:.7rem">CASH SALES</div>
                    <div class="fw-bold">GH₵ {{ number_format($summary['total_cash_sales'], 2) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="text-muted" style="font-size:.7rem">MTN MOMO</div>
                    <div class="fw-bold">GH₵ {{ number_format($summary['total_momo_sales'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Close Form --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-lock text-warning"></i> Close Day Form
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('day-closing.store') }}">
            @csrf
            <input type="hidden" name="branch_id" value="{{ $branch->id }}">

            @if(auth()->user()->isOwner())
            <div class="mb-3">
                <label class="form-label fw-semibold">Branch</label>
                <select name="branch_id" class="form-select" onchange="this.form.action='{{ route('day-closing.close') }}?branch_id='+this.value; this.form.method='GET'; this.form.submit();">
                    @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ $b->id == $branch->id ? 'selected' : '' }}>
                        {{ $b->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="mb-3">
                <label class="form-label fw-semibold">Opening Cash (GH₵) <span class="text-danger">*</span></label>
                <input type="number" name="opening_cash" id="openingCash"
                       class="form-control @error('opening_cash') is-invalid @enderror"
                       step="0.01" min="0" value="{{ old('opening_cash', 0) }}"
                       onchange="calcExpected()">
                <small class="text-muted">Amount of cash in drawer at start of day</small>
                @error('opening_cash')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Cash Counted Now (GH₵) <span class="text-danger">*</span></label>
                <input type="number" name="cash_counted" id="cashCounted"
                       class="form-control @error('cash_counted') is-invalid @enderror"
                       step="0.01" min="0" value="{{ old('cash_counted', 0) }}"
                       onchange="calcExpected()">
                <small class="text-muted">Physically count the cash in drawer right now</small>
                @error('cash_counted')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- Live Variance Calculator --}}
            <div class="alert alert-light border mb-3 p-2" id="varianceBox" style="font-size:.85rem">
                <div class="d-flex justify-content-between">
                    <span>Opening Cash</span>
                    <span id="vOpening">GH₵ 0.00</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>+ Cash Sales Today</span>
                    <span class="text-success">GH₵ {{ number_format($summary['total_cash_sales'], 2) }}</span>
                </div>
                <div class="d-flex justify-content-between border-top pt-1 mt-1">
                    <span>= Expected Cash</span>
                    <span id="vExpected" class="fw-semibold">GH₵ {{ number_format($summary['total_cash_sales'], 2) }}</span>
                </div>
                <div class="d-flex justify-content-between mt-1">
                    <span>Cash Counted</span>
                    <span id="vCounted">GH₵ 0.00</span>
                </div>
                <div class="d-flex justify-content-between border-top pt-1 mt-1 fw-bold">
                    <span>Variance</span>
                    <span id="vVariance" class="text-success">GH₵ 0.00</span>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Notes</label>
                <textarea name="notes" class="form-control" rows="2"
                          placeholder="Any notes about today's operations...">{{ old('notes') }}</textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-warning fw-bold"
                        onclick="return confirm('Are you sure? This will lock today\'s sales for {{ $branch->name }}.')">
                    <i class="bi bi-lock-fill"></i> Confirm Close Day
                </button>
                <a href="{{ route('day-closing.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

</div>
</div>

@push('scripts')
<script>
const cashSales = {{ $summary['total_cash_sales'] }};

function calcExpected() {
    const opening = parseFloat(document.getElementById('openingCash').value) || 0;
    const counted = parseFloat(document.getElementById('cashCounted').value) || 0;
    const expected = opening + cashSales;
    const variance = counted - expected;

    document.getElementById('vOpening').textContent  = 'GH₵ ' + opening.toFixed(2);
    document.getElementById('vExpected').textContent = 'GH₵ ' + expected.toFixed(2);
    document.getElementById('vCounted').textContent  = 'GH₵ ' + counted.toFixed(2);

    const vEl = document.getElementById('vVariance');
    vEl.textContent = (variance >= 0 ? '+' : '') + 'GH₵ ' + variance.toFixed(2);
    vEl.className   = variance < 0 ? 'text-danger fw-bold' : (variance > 0 ? 'text-warning fw-bold' : 'text-success fw-bold');
}
calcExpected();
</script>
@endpush
@endsection
