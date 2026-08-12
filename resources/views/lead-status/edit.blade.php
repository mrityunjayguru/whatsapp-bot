@extends('layout.master')

@section('title', 'Edit Lead Status')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
  <div>
    <h4 class="mb-3 mb-md-0">Edit Lead Status</h4>
  </div>
  <div>
    <a href="{{ route('lead-status.index') }}" class="btn btn-outline-secondary btn-icon-text">
      <i class="btn-icon-prepend" data-lucide="arrow-left"></i>
      Back
    </a>
  </div>
</div>

<div class="row">
  <div class="col-md-6 col-lg-5 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <form action="{{ route('lead-status.update', $leadStatus) }}" method="POST" novalidate>
          @csrf
          @method('PUT')

          <div class="mb-3">
            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" id="name" name="name"
              class="form-control @error('name') is-invalid @enderror"
              value="{{ old('name', $leadStatus->name) }}"
              placeholder="e.g. New, Contacted, Interested"
              autofocus>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="mb-4">
            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
            <select id="status" name="status"
              class="form-select @error('status') is-invalid @enderror">
              <option value="1" {{ old('status', $leadStatus->status) == '1' ? 'selected' : '' }}>Active</option>
              <option value="0" {{ old('status', $leadStatus->status) == '0' ? 'selected' : '' }}>Inactive</option>
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <button type="submit" class="btn btn-primary me-2">
            <i data-lucide="save" class="icon-sm me-1"></i> Update
          </button>
          <a href="{{ route('lead-status.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('custom-scripts')
<script>lucide.createIcons();</script>
@endpush
