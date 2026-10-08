@extends('layout.master')

@section('content')
<nav class="page-breadcrumb d-flex align-items-center mb-4">
  <a href="{{ route('whatsapp-numbers.index') }}" class="btn btn-outline-secondary me-3"><i data-lucide="arrow-left" class="icon-sm me-2"></i> Back to WhatsApp Numbers</a>
  <ol class="breadcrumb mb-0">
    <li class="breadcrumb-item"><a href="{{ route('whatsapp-numbers.index') }}">WhatsApp Numbers</a></li>
    <li class="breadcrumb-item active" aria-current="page">Register</li>
  </ol>
</nav>

@if ($errors->any())
  <div class="alert alert-danger mb-4">
    <ul class="mb-0">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="row justify-content-center">
  <div class="col-md-7">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title text-muted mb-4 border-bottom pb-2">REGISTER A CLIENT'S WHATSAPP NUMBER</h6>
        <p class="text-muted small">This number must already be set up in Meta's WhatsApp Business Platform (its own WABA, business-verified, with a generated access token) - this form just connects it to this Chatbot and gives it its own isolated FAQ knowledge base and bot persona.</p>

        <form action="{{ route('whatsapp-numbers.store') }}" method="POST">
          @csrf
          <div class="mb-3">
            <label class="form-label">Company <span class="text-danger">*</span></label>
            <select class="form-select @error('company_id') is-invalid @enderror" name="company_id" required>
                <option value="" disabled selected>Select a Company</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                        {{ $company->name }} ({{ $company->contact_email }})
                    </option>
                @endforeach
            </select>
            @error('company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Label <span class="text-muted small">(your own reference)</span></label>
            <input type="text" class="form-control" name="label" placeholder="e.g. Acme Corp - Support" value="{{ old('label') }}">
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Phone Number ID <span class="text-muted small">(optional)</span></label>
              <input type="text" class="form-control @error('phone_number_id') is-invalid @enderror" name="phone_number_id" placeholder="e.g. 111222333444555" value="{{ old('phone_number_id') }}">
              <small class="text-muted">Meta's own id for this number - from WhatsApp Business Platform &gt; API Setup, and present in every inbound webhook's metadata.</small>
              @error('phone_number_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">WABA ID <span class="text-muted small">(optional)</span></label>
              <input type="text" class="form-control" name="waba_id" value="{{ old('waba_id') }}">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Display Number <span class="text-muted small">(your own reference)</span></label>
            <input type="text" class="form-control" name="display_number" placeholder="+91 98765 43210" value="{{ old('display_number') }}">
          </div>

          <div class="mb-3">
            <label class="form-label">Access Token <span class="text-muted small">(optional)</span></label>
            <textarea class="form-control font-monospace small" name="access_token" rows="3" placeholder="Permanent or system-user access token from Meta">{{ old('access_token') }}</textarea>
            <small class="text-muted">Stored encrypted. Used only to send WhatsApp messages as THIS number - never shared with the Python bot service.</small>
            @error('access_token') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Graph API Version</label>
            <input type="text" class="form-control" name="graph_version" value="{{ old('graph_version', 'v23.0') }}">
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Valid From</label>
              <input type="date" class="form-control @error('valid_from') is-invalid @enderror" name="valid_from" value="{{ old('valid_from') }}">
              @error('valid_from') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Valid To (Expiry)</label>
              <input type="date" class="form-control @error('expiry_date') is-invalid @enderror" name="expiry_date" value="{{ old('expiry_date') }}">
              @error('expiry_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2 border-top pt-3 mt-3">
            <a href="{{ route('whatsapp-numbers.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Register Number</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
