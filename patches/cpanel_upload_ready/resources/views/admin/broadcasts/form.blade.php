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

                    {{-- AI Message Assistant --}}
                    <div class="alert alert-primary border-0 mb-4" style="background:#eef5ff;">
                        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                            <div>
                                <strong><i class="bi bi-stars"></i> AI Message Assistant</strong>
                                <div class="text-muted" style="font-size:.85rem;">Generate a starter message, then edit it before saving.</div>
                            </div>
                            <button type="button" id="generateTemplateBtn" class="btn btn-sm btn-primary">
                                <i class="bi bi-magic"></i> Generate Draft
                            </button>
                        </div>

                        <div class="row g-2 mt-1">
                            <div class="col-md-4">
                                <label for="templateType" class="form-label mb-1" style="font-size:.85rem;">Template Type</label>
                                <select id="templateType" class="form-select form-select-sm">
                                    @foreach($templateTypes as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="templateTone" class="form-label mb-1" style="font-size:.85rem;">Tone</label>
                                <select id="templateTone" class="form-select form-select-sm">
                                    @foreach($templateTones as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="eventName" class="form-label mb-1" style="font-size:.85rem;">Event Name (Optional)</label>
                                <input id="eventName" type="text" class="form-control form-control-sm" placeholder="E.g., Eid, Founder Day">
                            </div>
                            <div class="col-md-4">
                                <label for="effectiveDate" class="form-label mb-1" style="font-size:.85rem;">Effective Date</label>
                                <input id="effectiveDate" type="date" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-4">
                                <label for="reopenDate" class="form-label mb-1" style="font-size:.85rem;">Reopen Date</label>
                                <input id="reopenDate" type="date" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-4">
                                <label for="extraNote" class="form-label mb-1" style="font-size:.85rem;">Extra Note (Optional)</label>
                                <input id="extraNote" type="text" class="form-control form-control-sm" placeholder="Any extra detail for customers">
                            </div>
                        </div>
                        <small id="templateStatus" class="text-muted d-block mt-2"></small>
                    </div>

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
                        <button type="submit" name="submit_action" value="draft" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-megaphone"></i> Create as Draft
                        </button>
                        <button type="submit" name="submit_action" value="send_now" class="btn btn-success flex-grow-1">
                            <i class="bi bi-send"></i> Send Now
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
const messageField = document.querySelector('textarea[name="message"]');
const titleField = document.querySelector('input[name="title"]');
const charCount = document.getElementById('charCount');
const templateStatus = document.getElementById('templateStatus');
const generateBtn = document.getElementById('generateTemplateBtn');

function updateCharCount() {
    charCount.textContent = messageField.value.length;
}

messageField.addEventListener('input', updateCharCount);
updateCharCount();

generateBtn.addEventListener('click', async function () {
    templateStatus.textContent = 'Generating draft message...';
    templateStatus.className = 'text-muted d-block mt-2';
    generateBtn.disabled = true;

    try {
        const response = await fetch("{{ route('admin.broadcasts.generate-template') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                template_type: document.getElementById('templateType').value,
                tone: document.getElementById('templateTone').value,
                event_name: document.getElementById('eventName').value,
                effective_date: document.getElementById('effectiveDate').value,
                reopen_date: document.getElementById('reopenDate').value,
                extra_note: document.getElementById('extraNote').value
            })
        });

        const payload = await response.json();

        if (!response.ok) {
            throw new Error(payload.message || 'Could not generate template.');
        }

        titleField.value = payload.title || '';
        messageField.value = payload.message || '';

        if (payload.channel) {
            const targetChannel = document.querySelector(`input[name="channel"][value="${payload.channel}"]`);
            if (targetChannel) {
                targetChannel.checked = true;
            }
        }

        updateCharCount();
        templateStatus.textContent = 'Draft generated. You can edit title and message before saving.';
        templateStatus.className = 'text-success d-block mt-2';
    } catch (error) {
        templateStatus.textContent = error.message || 'Failed to generate template.';
        templateStatus.className = 'text-danger d-block mt-2';
    } finally {
        generateBtn.disabled = false;
    }
});
</script>
@endsection
