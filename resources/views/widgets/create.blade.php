@extends('layout.master')

@section('content')
<nav class="page-breadcrumb d-flex align-items-center mb-4">
  <a href="{{ route('widgets.index') }}" class="btn btn-outline-secondary me-3"><i data-lucide="arrow-left" class="icon-sm me-2"></i> Back to Widgets</a>
  <ol class="breadcrumb mb-0">
    <li class="breadcrumb-item"><a href="{{ route('widgets.index') }}">Widgets</a></li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
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
  <div class="col-md-6">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title text-muted mb-4 border-bottom pb-2">NEW WEBSITE WIDGET</h6>

        <form action="{{ route('widgets.store') }}" method="POST">
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
            <label class="form-label">Site name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="site_name" placeholder="e.g. Acme Corp Website" required value="{{ old('site_name') }}">
            <small class="text-muted">Just a label for you to recognize it by - doesn't appear to visitors.</small>
          </div>

          <div class="mb-3">
            <label class="form-label">Client contact email</label>
            <input type="email" class="form-control" name="contact_email" placeholder="client@example.com" value="{{ old('contact_email') }}">
            <small class="text-muted">For your own reference - who to contact about this widget. Optional.</small>
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
            <a href="{{ route('widgets.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Create Widget</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
