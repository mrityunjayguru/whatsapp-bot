@extends('layout.master')

@php
    $phoneNumberId = $number->phone_number_id;
@endphp

@section('content')
<nav class="page-breadcrumb d-flex align-items-center mb-4">
  <a href="{{ route('whatsapp-numbers.index') }}" class="btn btn-outline-secondary me-3"><i data-lucide="arrow-left" class="icon-sm me-2"></i> Back to WhatsApp Numbers</a>
  <ol class="breadcrumb mb-0">
    <li class="breadcrumb-item"><a href="{{ route('whatsapp-numbers.index') }}">WhatsApp Numbers</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $number->label ?: $number->display_number ?: $phoneNumberId }}</li>
  </ol>
</nav>

@if (session('success'))
  <div class="alert alert-success mb-4">{{ session('success') }}</div>
@endif
@if (session('error'))
  <div class="alert alert-danger mb-4">{{ session('error') }}</div>
@endif
@if ($errors->any())
  <div class="alert alert-danger mb-4">
    <ul class="mb-0">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="card mb-3">
  <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h6 class="card-title text-muted mb-1">BOT PERSONA</h6>
      <p class="text-muted small mb-0">Company name, links, products, and canned reply text for THIS number's bot - independent of every other registered number.</p>
    </div>
    <a href="{{ route('whatsapp-numbers.bot-config.edit', $phoneNumberId) }}" class="btn btn-outline-primary">
      <i data-lucide="settings" class="icon-sm me-1"></i> Edit Bot Persona
    </a>
  </div>
</div>

<div class="row">
  <!-- Left: number config -->
  <div class="col-md-12">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title text-muted mb-4 border-bottom pb-2">NUMBER SETTINGS</h6>

        <form action="{{ route('whatsapp-numbers.config.update', $phoneNumberId) }}" method="POST">
          @csrf
          @method('PUT')

          @if(!$isCompanyUser)
          <div class="mb-3">
            <label class="form-label">Company</label>
            <select name="company_id" class="form-select">
              <option value="">-- No Company --</option>
              @foreach($companies as $c)
                <option value="{{ $c->id }}" @selected($number->company_id == $c->id)>{{ $c->name }}</option>
              @endforeach
            </select>
            <small class="text-muted">Reassign this number to a different company, or unassign it.</small>
          </div>
          @endif

          <div class="mb-3">
            <label class="form-label">Phone Number ID <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="phone_number_id" value="{{ old('phone_number_id', $phoneNumberId) }}" required>
            <small class="text-muted">Meta's phone number ID. Changing this will update the webhook routing.</small>
          </div>

          <div class="mb-3">
            <label class="form-label">Label <span class="text-muted small">(your own reference)</span></label>
            <input type="text" class="form-control" name="label" value="{{ old('label', $number->label) }}">
          </div>

          <div class="mb-3">
            <label class="form-label">Display Number</label>
            <input type="text" class="form-control" name="display_number" value="{{ old('display_number', $number->display_number) }}">
          </div>

          <div class="mb-3">
            <label class="form-label">WABA ID</label>
            <input type="text" class="form-control" name="waba_id" value="{{ old('waba_id', $number->waba_id) }}">
          </div>

          <div class="mb-3">
            <label class="form-label">Access Token <span class="text-muted small">(leave blank to keep the current one)</span></label>
            <textarea class="form-control font-monospace small" name="access_token" rows="2" placeholder="Only fill this in to replace the saved token"></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Graph API Version</label>
            <input type="text" class="form-control" name="graph_version" value="{{ old('graph_version', $number->graph_version) }}">
          </div>

          <div class="row mb-4">
            <div class="col-md-6 mb-3 mb-md-0">
              <label class="form-label">Valid From <span class="text-muted small">(Leave empty for no start date)</span></label>
              <input type="date" class="form-control" name="valid_from" value="{{ old('valid_from', optional($number->valid_from)->format('Y-m-d')) }}">
            </div>
            <div class="col-md-6">
              <label class="form-label">Valid To (Expiry) <span class="text-muted small">(Leave empty for no expiry)</span></label>
              <input type="date" class="form-control" name="expiry_date" value="{{ old('expiry_date', optional($number->expiry_date)->format('Y-m-d')) }}">
            </div>
          </div>

          <div class="d-flex align-items-center justify-content-between bg-light p-3 rounded mb-4">
            <div>
              <p class="mb-0 fw-bold">Number Status</p>
              <small class="text-muted">Turn off to take it offline without deleting it</small>
            </div>
            <div class="form-check form-switch mb-0">
              <input type="checkbox" class="form-check-input" name="is_active" value="1" @checked(old('is_active', $number->is_active)) style="width: 40px; height: 20px;">
            </div>
          </div>

          <div class="d-flex justify-content-end border-top pt-3">
            <button type="submit" class="btn btn-primary">Save Settings</button>
          </div>
        </form>
      </div>
    </div>

    <!-- <div class="card mt-3">
      <div class="card-body">
        <h6 class="card-title text-muted mb-4 border-bottom pb-2">BOT MESSAGES</h6>
        
        <form action="{{ route('whatsapp-numbers.messages.update', $phoneNumberId) }}" method="POST" id="messagesForm">
          @csrf
          @method('PUT')
          <div class="mb-3">
            <label class="form-label">Greeting Message <span class="text-muted small">(when someone says hi)</span></label>
            <textarea class="form-control" name="greeting" id="greeting_message" rows="4">{{ old('greeting', $botConfig['templates']['greeting'] ?? '') }}</textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Fallback Message <span class="text-muted small">(when nothing else matched)</span></label>
            <textarea class="form-control" name="fallback" id="fallback_message" rows="4">{{ old('fallback', $botConfig['templates']['fallback'] ?? '') }}</textarea>
          </div>
          <div class="d-flex justify-content-end border-top pt-3">
            <button type="submit" class="btn btn-primary" id="saveMessagesBtn">Save Messages</button>
          </div>
        </form>
      </div>
    </div> -->

    @if(!$isCompanyUser)
    <div class="card mt-3 border-danger">
      <div class="card-body">
        <h6 class="card-title text-danger mb-3 border-bottom pb-2">DANGER ZONE</h6>
        <p class="text-muted small mb-3">Permanently removes this number's config, every FAQ, its bot persona, and its stored access token. The number will stop receiving automated replies immediately. This cannot be undone.</p>
        <form action="{{ route('whatsapp-numbers.destroy', $phoneNumberId) }}" method="POST"
              onsubmit="return confirm('Permanently remove \'{{ $number->label ?: $phoneNumberId }}\' and ALL of its FAQs? This cannot be undone.');">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-outline-danger w-100">Remove This Number Permanently</button>
        </form>
      </div>
    </div>
    @endif
  </div>


</div>
@endsection

@push('custom-scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        tinymce.init({
            selector: '#answer, #greeting_message, #fallback_message',
            menubar: false,
            plugins: 'link lists',
            toolbar: 'undo redo | bold italic | alignleft aligncenter alignright | bullist numlist | link',
            promotion: false,
            branding: false,
            setup: function(editor) {
                editor.on('submit', function() {
                    editor.save();
                });
            }
        });

        // Sync TinyMCE content to textarea before form submit and validate for FAQ
        var addBtn = document.getElementById('addFaqBtn');
        if (addBtn) {
            addBtn.closest('form').addEventListener('submit', function(e) {
                tinymce.triggerSave();
                var val = document.getElementById('answer').value.trim();
                if (!val || val === '<p></p>' || val === '<p><br></p>') {
                    e.preventDefault();
                    alert('Please enter an answer.');
                    tinymce.get('answer').focus();
                }
            });
        }

        // Sync TinyMCE content to textarea before form submit and validate for Messages
        var msgForm = document.getElementById('messagesForm');
        if (msgForm) {
            msgForm.addEventListener('submit', function(e) {
                tinymce.triggerSave();
                var greeting = document.getElementById('greeting_message').value.trim();
                if (!greeting || greeting === '<p></p>' || greeting === '<p><br></p>') {
                    e.preventDefault();
                    alert('Please enter a greeting message.');
                    tinymce.get('greeting_message').focus();
                    return;
                }
                var fallback = document.getElementById('fallback_message').value.trim();
                if (!fallback || fallback === '<p></p>' || fallback === '<p><br></p>') {
                    e.preventDefault();
                    alert('Please enter a fallback message.');
                    tinymce.get('fallback_message').focus();
                    return;
                }
            });
        }
    });
</script>
@endpush
