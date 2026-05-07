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

{{-- ─── MTN MoMo ──────────────────────────────────────── --}}
<div class="row g-4 mt-0">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
                <img src="https://upload.wikimedia.org/wikipedia/commons/9/93/New-mtn-logo.jpg"
                     alt="MTN" style="height:28px; width:auto; border-radius:4px">
                <div>
                    <div class="fw-bold">MTN Mobile Money (MoMo) Configuration</div>
                    <small class="text-muted">Merchant: DAAB C26 ENTERPRISE &mdash; ID: 047100 &mdash; Number: 0557115748</small>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">

                    <div class="col-lg-4">
                        <label class="form-label fw-semibold">Target Environment</label>
                        <select name="MTN_MOMO_TARGET_ENVIRONMENT" class="form-select">
                            <option value="sandbox" {{ env('MTN_MOMO_TARGET_ENVIRONMENT', 'sandbox') === 'sandbox' ? 'selected' : '' }}>Sandbox (Testing)</option>
                            <option value="mtnghana" {{ env('MTN_MOMO_TARGET_ENVIRONMENT') === 'mtnghana' ? 'selected' : '' }}>MTN Ghana (Live)</option>
                            <option value="mtncongo" {{ env('MTN_MOMO_TARGET_ENVIRONMENT') === 'mtncongo' ? 'selected' : '' }}>MTN Congo (Live)</option>
                        </select>
                        <div class="form-text">Use <strong>mtnghana</strong> for live Ghana transactions.</div>
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label fw-semibold">Base URL</label>
                        <input type="text" name="MTN_MOMO_BASE_URL"
                               class="form-control font-monospace"
                               value="{{ env('MTN_MOMO_BASE_URL', 'https://sandbox.momodeveloper.mtn.com') }}"
                               placeholder="https://sandbox.momodeveloper.mtn.com">
                        <div class="form-text">Sandbox: <code>sandbox.momodeveloper.mtn.com</code></div>
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label fw-semibold">Currency</label>
                        <input type="text" name="MTN_MOMO_CURRENCY"
                               class="form-control"
                               value="{{ env('MTN_MOMO_CURRENCY', 'GHS') }}"
                               maxlength="5"
                               placeholder="GHS">
                        <div class="form-text">3-letter currency code. Use <strong>GHS</strong> for Ghana.</div>
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label fw-semibold">Subscription Key</label>
                        <input type="text" name="MTN_MOMO_SUBSCRIPTION_KEY"
                               class="form-control font-monospace"
                               value="{{ env('MTN_MOMO_SUBSCRIPTION_KEY') }}"
                               placeholder="From MTN MoMo Developer portal">
                        <div class="form-text">Primary/Secondary key from your Collection product.</div>
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label fw-semibold">API User (UUID)</label>
                        <input type="text" name="MTN_MOMO_API_USER"
                               class="form-control font-monospace"
                               value="{{ env('MTN_MOMO_API_USER') }}"
                               placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                        <div class="form-text">Generated UUID via MTN /v1_0/apiuser endpoint.</div>
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label fw-semibold">API Key</label>
                        <div class="input-group">
                            <input type="password" name="MTN_MOMO_API_KEY" id="momoApiKeyInput"
                                   class="form-control font-monospace"
                                   value="{{ env('MTN_MOMO_API_KEY') }}"
                                   placeholder="••••••••">
                            <button class="btn btn-outline-secondary" type="button"
                                    onclick="toggleMomoKey()">
                                <i class="bi bi-eye" id="momoApiKeyIcon"></i>
                            </button>
                        </div>
                        <div class="form-text">Generated via MTN /v1_0/apiuser/{apiUser}/apikey.</div>
                    </div>

                    <div class="col-lg-8">
                        <label class="form-label fw-semibold">Callback URL</label>
                        <input type="url" name="MTN_MOMO_CALLBACK_URL"
                               class="form-control font-monospace"
                               value="{{ env('MTN_MOMO_CALLBACK_URL', url('/webhooks/mtn/momo')) }}"
                               placeholder="https://yourdomain.com/webhooks/mtn/momo">
                        <div class="form-text">Must be a public HTTPS URL. MTN posts payment result here.</div>
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label fw-semibold">Merchant Name</label>
                        <input type="text" name="MTN_MOMO_MERCHANT_NAME"
                               class="form-control"
                               value="{{ env('MTN_MOMO_MERCHANT_NAME', 'DAAB C26 ENTERPRISE') }}"
                               placeholder="DAAB C26 ENTERPRISE">
                    </div>

                </div>

                <div class="alert alert-info py-2 mt-3 mb-0" style="font-size:.82rem">
                    <i class="bi bi-info-circle"></i>
                    Get your credentials from
                    <a href="https://momodeveloper.mtn.com" target="_blank" rel="noopener noreferrer">momodeveloper.mtn.com</a>.
                    Subscribe to the <strong>Collection</strong> product, then create an API user &amp; key using the Sandbox Provisioning API or the portal.
                    Set <strong>Target Environment</strong> to <strong>mtnghana</strong> when going live.
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

function toggleMomoKey() {
    const input = document.getElementById('momoApiKeyInput');
    const icon  = document.getElementById('momoApiKeyIcon');
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
