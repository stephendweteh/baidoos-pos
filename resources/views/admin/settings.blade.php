@extends('layouts.app')
@section('title', 'System Settings')
@section('page-title', 'System Settings')

@section('content')
<form method="POST" action="{{ route('superadmin.settings.update') }}">
@csrf

<div class="row g-4">

    {{-- ─── SMS / Arkesel ─────────────────────────────── --}}
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
                <i class="bi bi-chat-dots-fill text-primary fs-5"></i>
                <div>
                    <div class="fw-bold">SMS Configuration</div>
                    <small class="text-muted">Arkesel API — used for sending payment receipts via SMS</small>
                </div>
            </div>
            <div class="card-body">

                <div class="mb-3">
                    <label class="form-label fw-semibold">API Key</label>
                    <input type="text" name="ARKESEL_API_KEY"
                           class="form-control font-monospace"
                           value="{{ env('ARKESEL_API_KEY') }}"
                           placeholder="Your Arkesel API key">
                    <div class="form-text">Find this in your <a href="https://arkesel.com" target="_blank" rel="noopener noreferrer">Arkesel dashboard</a>.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Sender ID</label>
                    <input type="text" name="ARKESEL_SENDER_ID"
                           class="form-control"
                           value="{{ env('ARKESEL_SENDER_ID', 'BaidoosPOS') }}"
                           maxlength="11"
                           placeholder="e.g. BaidoosPOS">
                    <div class="form-text">Max 11 characters. Must be registered/approved on Arkesel.</div>
                </div>

                <div class="alert alert-info py-2 mb-0" style="font-size:.82rem">
                    <i class="bi bi-info-circle"></i>
                    SMS receipts are sent automatically when a phone number is provided at checkout.
                    Leave the API key blank to disable SMS sending.
                </div>
            </div>
        </div>
    </div>

    {{-- ─── SMTP / Mail ────────────────────────────────── --}}
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
                <i class="bi bi-envelope-fill text-success fs-5"></i>
                <div>
                    <div class="fw-bold">SMTP / Email Configuration</div>
                    <small class="text-muted">Used for sending email notifications and receipts</small>
                </div>
            </div>
            <div class="card-body">

                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label fw-semibold">Mailer</label>
                        <select name="MAIL_MAILER" class="form-select">
                            @foreach(['smtp' => 'SMTP', 'sendmail' => 'Sendmail', 'log' => 'Log (testing)', 'array' => 'Array (testing)'] as $val => $label)
                            <option value="{{ $val }}" {{ env('MAIL_MAILER', 'smtp') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-semibold">Encryption</label>
                        <select name="MAIL_ENCRYPTION" class="form-select">
                            @foreach(['tls' => 'TLS', 'ssl' => 'SSL', '' => 'None'] as $val => $label)
                            <option value="{{ $val }}" {{ env('MAIL_ENCRYPTION', 'tls') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-sm-8">
                        <label class="form-label fw-semibold">Host</label>
                        <input type="text" name="MAIL_HOST"
                               class="form-control"
                               value="{{ env('MAIL_HOST') }}"
                               placeholder="smtp.gmail.com">
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label fw-semibold">Port</label>
                        <input type="number" name="MAIL_PORT"
                               class="form-control"
                               value="{{ env('MAIL_PORT', 587) }}"
                               placeholder="587">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Username</label>
                    <input type="text" name="MAIL_USERNAME"
                           class="form-control"
                           value="{{ env('MAIL_USERNAME') }}"
                           placeholder="your@email.com">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Password / App Password</label>
                    <div class="input-group">
                        <input type="password" name="MAIL_PASSWORD" id="mailPasswordInput"
                               class="form-control font-monospace"
                               value="{{ env('MAIL_PASSWORD') }}"
                               placeholder="••••••••">
                        <button class="btn btn-outline-secondary" type="button"
                                onclick="toggleMailPassword()" id="mailPasswordToggle">
                            <i class="bi bi-eye" id="mailPasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label fw-semibold">From Address</label>
                        <input type="email" name="MAIL_FROM_ADDRESS"
                               class="form-control"
                               value="{{ env('MAIL_FROM_ADDRESS') }}"
                               placeholder="no-reply@yourdomain.com">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-semibold">From Name</label>
                        <input type="text" name="MAIL_FROM_NAME"
                               class="form-control"
                               value="{{ env('MAIL_FROM_NAME', 'Baidoos POS') }}"
                               placeholder="Baidoos POS">
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>

{{-- Save button --}}
<div class="mt-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-floppy2-fill"></i> Save Settings
    </button>
    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Cancel</a>
</div>

</form>
@endsection

@push('scripts')
<script>
function toggleMailPassword() {
    const input = document.getElementById('mailPasswordInput');
    const icon  = document.getElementById('mailPasswordIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>
@endpush
