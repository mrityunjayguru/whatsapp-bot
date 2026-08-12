@extends('layout.master')

@section('title', 'Edit Company')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
  <div>
    <h4 class="mb-3 mb-md-0">Edit Company</h4>
  </div>
  <div>
    <a href="{{ route('company.index') }}" class="btn btn-outline-secondary btn-icon-text">
      <i class="btn-icon-prepend" data-lucide="arrow-left"></i>
      Back
    </a>
  </div>
</div>

<div class="row">
  <div class="col-md-8 col-lg-7 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <form action="{{ route('company.update', $company) }}" method="POST" novalidate>
          @csrf
          @method('PUT')

          {{-- Name --}}
          <div class="mb-3">
            <label for="name" class="form-label">Company Name <span class="text-danger">*</span></label>
            <input
              type="text"
              id="name"
              name="name"
              class="form-control @error('name') is-invalid @enderror"
              value="{{ old('name', $company->name) }}"
              placeholder="Enter company name"
              autofocus
            >
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          {{-- Email --}}
          <div class="mb-3">
            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
            <input
              type="email"
              id="email"
              name="email"
              class="form-control @error('email') is-invalid @enderror"
              value="{{ old('email', $company->email) }}"
              placeholder="Enter email address"
            >
            @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          {{-- Contact Number --}}
          <div class="mb-3">
            <label for="contact_number" class="form-label">Contact Number <span class="text-danger">*</span></label>
            <input
              type="text"
              id="contact_number"
              name="contact_number"
              class="form-control @error('contact_number') is-invalid @enderror"
              value="{{ old('contact_number', $company->contact_number) }}"
              placeholder="Enter contact number"
            >
            @error('contact_number')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          {{-- Password (optional on edit) --}}
          <div class="mb-3">
            <label for="password" class="form-label">
              New Password
              <small class="text-muted">(leave blank to keep current)</small>
            </label>
            <div class="input-group">
              <input
                type="password"
                id="password"
                name="password"
                class="form-control @error('password') is-invalid @enderror"
                placeholder="Minimum 8 characters"
                autocomplete="new-password"
              >
              <button class="btn btn-outline-secondary" type="button" id="togglePassword" tabindex="-1">
                <i data-lucide="eye" class="icon-sm" id="eyeIcon"></i>
              </button>
              @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          {{-- Confirm Password --}}
          <div class="mb-3">
            <label for="password_confirmation" class="form-label">Confirm New Password</label>
            <div class="input-group">
              <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                class="form-control"
                placeholder="Re-enter new password"
                autocomplete="new-password"
              >
              <button class="btn btn-outline-secondary" type="button" id="toggleConfirm" tabindex="-1">
                <i data-lucide="eye" class="icon-sm" id="eyeIconConfirm"></i>
              </button>
            </div>
          </div>

          {{-- Status --}}
          <div class="mb-4">
            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
            <select
              id="status"
              name="status"
              class="form-select @error('status') is-invalid @enderror"
            >
              <option value="1" {{ old('status', $company->status) == '1' ? 'selected' : '' }}>Active</option>
              <option value="0" {{ old('status', $company->status) == '0' ? 'selected' : '' }}>Inactive</option>
            </select>
            @error('status')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <button type="submit" class="btn btn-primary me-2">
            <i data-lucide="save" class="icon-sm me-1"></i> Update Company
          </button>
          <a href="{{ route('company.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('custom-scripts')
<script>
  lucide.createIcons();

  function toggleVisiblity(btnId, inputId, iconId) {
    document.getElementById(btnId).addEventListener('click', function () {
      const input = document.getElementById(inputId);
      const icon  = document.getElementById(iconId);
      if (input.type === 'password') {
        input.type = 'text';
        icon.setAttribute('data-lucide', 'eye-off');
      } else {
        input.type = 'password';
        icon.setAttribute('data-lucide', 'eye');
      }
      lucide.createIcons();
    });
  }

  toggleVisiblity('togglePassword', 'password', 'eyeIcon');
  toggleVisiblity('toggleConfirm', 'password_confirmation', 'eyeIconConfirm');
</script>
@endpush
