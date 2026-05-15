@extends('layouts.app')
@section('title', 'Create Broadcast')
@section('page-title', 'Create Broadcast')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.broadcasts.store') }}">
                    @csrf

                    {{-- Title --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Message Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" 
                               value="{{ old('title', $broadcast->title ?? '') }}"
                               class="form-control @error('title') is-invalid @enderror"
                               placeholder="E.g., Holiday Promotion, System Maintenance" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Max 150 characters</small>
                    </div>

                    {{-- Message --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Message <span class="text-danger">*</span></label>
                        <textarea name="message" rows="8"
                                  class="form-control @error('message') is-invalid @enderror"
                                  placeholder="Write your broadcast message here..."
                                  required>{{ old('message', $broadcast->message ?? '') }}</textarea>
                        @error('message')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted"><span id="charCount">0</span>/1000 characters</small>
                    </div>

                    {{-- Channel Selection --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Send Via <span class="text-danger">*</span></label>
                        <div class="d-grid gap-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="channel" id="channelSms" 
                                       value="sms" {{ old('channel', 'both') === 'sms' ? 'checked' : '' }}>
                                <label class="form-check-label" for="channelSms">
                                    📞 <strong>SMS Only</strong>
                                    <br><small class="text-muted">Sent to customers with phone numbers</small>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="channel" id="channelEmail" 
                                       value="email" {{ old('channel', 'both') === 'email' ? 'checked' : '' }}>
                                <label class="form-check-label" for="channelEmail">
                                    ✉️ <strong>Email Only</strong>
                                    <br><small class="text-muted">Sent to customers with email addresses</small>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="channel" id="channelBoth" 
                                       value="both" {{ old('channel', 'both') === 'both' ? 'checked' : '' }}>
                                <label class="form-check-label" for="channelBoth">
                                    📞✉️ <strong>Both SMS & Email</strong>
                                    <br><small class="text-muted">Sent to all customers (uses available contact)</small>
                                </label>
                            </div>
                        </div>
                        @error('channel')
                            <div class="alert alert-danger mt-2" style="font-size:.85rem">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Recipients Info --}}
                    <div class="alert alert-info py-2 px-3" style="font-size:.9rem">
                        <i class="bi bi-info-circle"></i>
                        <strong>Recipients:</strong> {{ $customerCount }} active customers
                    </div>

                    {{-- Form Actions --}}
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-megaphone"></i> Create as Draft
                        </button>
                        <a href="{{ route('admin.broadcasts.index') }}" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="alert alert-light mt-3 p-3" style="font-size:.85rem">
            <strong>📝 Note:</strong> Broadcasts are created as drafts. You'll review the message and choose to send when ready.
        </div>
    </div>
</div>

<script>
document.querySelector('textarea[name="message"]').addEventListener('input', function () {
    document.getElementById('charCount').textContent = this.value.length;
});

// Initialize char count
document.getElementById('charCount').textContent = document.querySelector('textarea[name="message"]').value.length;
</script>
@endsection
