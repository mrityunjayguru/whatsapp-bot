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
        
        <h6 class="card-title mb-4">Edit Company</h6>

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
                <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('contact_number') is-invalid @enderror" name="contact_number" value="{{ old('contact_number', $company->contact_number) }}">
                @error('contact_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Password <small class="text-muted">(Leave blank to keep current password)</small></label>
                <div class="input-group">
                  <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="Minimum 8 characters">
                  <span class="input-group-text"><i data-lucide="eye" class="icon-sm"></i></span>
                </div>
                @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <div class="input-group">
                  <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" name="password_confirmation" placeholder="Re-enter password">
                  <span class="input-group-text"><i data-lucide="eye" class="icon-sm"></i></span>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Status <span class="text-danger">*</span></label>
                <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                    <option value="ACTIVE" {{ old('status', $company->is_active ? 'ACTIVE' : 'INACTIVE') == 'ACTIVE' ? 'selected' : '' }}>Active</option>
                    <option value="INACTIVE" {{ old('status', $company->is_active ? 'ACTIVE' : 'INACTIVE') == 'INACTIVE' ? 'selected' : '' }}>Inactive</option>
                </select>
                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
