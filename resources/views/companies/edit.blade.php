@extends('layout.master')

@section('title', 'Edit Company')

@section('content')
<nav class="page-breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-lucide="home" class="icon-sm"></i></a></li>
    <li class="breadcrumb-item"><a href="{{ route('companies.index') }}">Companies</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit Company</li>
  </ol>
</nav>

<div class="row">
  <div class="col-md-8 grid-margin stretch-card mx-auto">
    <div class="card">
      <div class="card-body">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h6 class="card-title mb-0">Edit Company</h6>
            @if($company->company_code)
                <span class="badge bg-light text-dark border px-3 py-2 fs-6 font-monospace">
                    ID: {{ $company->company_code }}
                </span>
            @endif
        </div>

        <form action="{{ route('companies.update', $company->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="mb-3">
                <label class="form-label">Company Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $company->name) }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $company->contact_email) }}" required>
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Contact Number</label>
                <input type="text" class="form-control @error('contact_number') is-invalid @enderror" name="contact_number" value="{{ old('contact_number', $company->contact_number) }}">
                @error('contact_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Password <small class="text-muted">(Leave blank to keep current password)</small></label>
                <div class="input-group">
                  <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" id="password" placeholder="Minimum 8 characters">
                  <button class="btn btn-outline-secondary" type="button" id="togglePassword" tabindex="-1">
                    <i data-lucide="eye" id="eyeIconPassword" class="icon-sm"></i>
                  </button>
                </div>
                @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <div class="input-group">
                  <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" name="password_confirmation" id="password_confirmation" placeholder="Re-enter password">
                  <button class="btn btn-outline-secondary" type="button" id="togglePasswordConfirm" tabindex="-1">
                    <i data-lucide="eye" id="eyeIconConfirm" class="icon-sm"></i>
                  </button>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label d-block">What you want to use for bot? <span class="text-danger">*</span></label>
                <div class="form-check form-check-inline">
                    <input type="radio" class="form-check-input @error('bot_usage_type') is-invalid @enderror" name="bot_usage_type" id="bot_whatsapp" value="whatsapp" {{ old('bot_usage_type', $company->bot_usage_type) == 'whatsapp' ? 'checked' : '' }} required>
                    <label class="form-check-label" for="bot_whatsapp">Whatsapp bot</label>
                </div>
                <div class="form-check form-check-inline">
                    <input type="radio" class="form-check-input @error('bot_usage_type') is-invalid @enderror" name="bot_usage_type" id="bot_widget" value="widget" {{ old('bot_usage_type', $company->bot_usage_type) == 'widget' ? 'checked' : '' }} required>
                    <label class="form-check-label" for="bot_widget">Widget bot</label>
                </div>
                @error('bot_usage_type') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            <div class="card bg-light border p-3 mb-4 rounded">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="fw-bold mb-0 text-dark"><i data-lucide="layers" class="icon-sm me-1 text-primary"></i> CRM Integration</h6>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="crm_integration_enabled" id="crm_integration_enabled" value="1" {{ old('crm_integration_enabled', $company->crm_integration_enabled) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="crm_integration_enabled">Enable Send to CRM</label>
                    </div>
                </div>
                <small class="text-muted d-block mb-3">If enabled, agents can send conversations to CRM as unassigned leads.</small>

                <div id="crmConfigFields" style="{{ old('crm_integration_enabled', $company->crm_integration_enabled) ? '' : 'display: none;' }}">
                    <div class="mb-3">
                        <label class="form-label">CRM API URL <small class="text-muted">(Default: http://localhost/CRM/public/api/leads/store-from-chatbot)</small></label>
                        <input type="text" class="form-control" name="crm_api_url" value="{{ old('crm_api_url', $company->crm_api_url ?? 'http://localhost/CRM/public/api/leads/store-from-chatbot') }}" placeholder="http://localhost/CRM/public/api/leads/store-from-chatbot">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">CRM API Token / Secret <small class="text-muted">(Optional)</small></label>
                        <input type="text" class="form-control" name="crm_api_token" value="{{ old('crm_api_token', $company->crm_api_token) }}" placeholder="Optional API Token">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary submit">
                <i data-lucide="save" class="icon-sm me-1"></i> Update Company
            </button>
            <a href="{{ route('companies.index') }}" class="btn btn-secondary">Cancel</a>
        </form>

      </div>
    </div>
  </div>
</div>

@endsection

@push('custom-scripts')
<script>
    function setupToggle(btnId, inputId, iconId) {
        var btn = document.getElementById(btnId);
        var input = document.getElementById(inputId);
        var icon = document.getElementById(iconId);
        if (!btn || !input || !icon) return;
        btn.addEventListener('click', function() {
            var isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            icon.setAttribute('data-lucide', isPassword ? 'eye-off' : 'eye');
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    }
    document.addEventListener('DOMContentLoaded', function() {
        setupToggle('togglePassword', 'password', 'eyeIconPassword');
        setupToggle('togglePasswordConfirm', 'password_confirmation', 'eyeIconConfirm');

        var crmSwitch = document.getElementById('crm_integration_enabled');
        var crmFields = document.getElementById('crmConfigFields');
        if (crmSwitch && crmFields) {
            crmSwitch.addEventListener('change', function() {
                crmFields.style.display = this.checked ? 'block' : 'none';
            });
        }
    });
</script>
@endpush
