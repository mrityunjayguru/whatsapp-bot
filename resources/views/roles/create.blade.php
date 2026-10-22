@extends('layout.master')

@section('title', 'Create Role')

@section('content')
<nav class="page-breadcrumb d-flex justify-content-between align-items-center">
  <ol class="breadcrumb mb-0">
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-lucide="home" class="icon-sm"></i></a></li>
    <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">Roles & Permissions</a></li>
    <li class="breadcrumb-item active" aria-current="page">Create Role</li>
  </ol>
  <div>
    <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
      <i data-lucide="arrow-left" class="icon-sm me-1"></i> Back to Roles
    </a>
  </div>
</nav>

<form action="{{ route('roles.store') }}" method="POST">
  @csrf

  <div class="row">
    <!-- Role Details Column -->
    <div class="col-lg-4 mb-4">
      <div class="card shadow-sm sticky-top" style="top: 80px; z-index: 1;">
        <div class="card-body">
          <h6 class="card-title mb-3"><i data-lucide="shield" class="icon-sm text-primary me-1"></i> Role Details</h6>
          <p class="text-secondary small mb-3">Define the role identity and its purpose within your company.</p>

          <div class="mb-3">
            <label class="form-label fw-bold">Role Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="e.g. Senior Support Agent, Team Lead" value="{{ old('name') }}" required autofocus>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">Description</label>
            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="Explain what members with this role are responsible for...">{{ old('description') }}</textarea>
            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="border-top pt-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="small fw-semibold text-muted">Selected Permissions:</span>
              <span class="badge bg-primary rounded-pill px-3 py-1" id="selectedCountBadge">0</span>
            </div>
            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-primary fw-bold">
                <i data-lucide="save" class="icon-sm me-1"></i> Save Role
              </button>
              <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Permission Matrix Column -->
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
              <h6 class="card-title mb-1"><i data-lucide="key" class="icon-sm text-primary me-1"></i> Permission Matrix</h6>
              <p class="text-secondary fs-13px mb-0">Check the individual actions members with this role are allowed to execute.</p>
            </div>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-xs btn-outline-primary" id="btnSelectAll">Select All</button>
              <button type="button" class="btn btn-xs btn-outline-secondary" id="btnDeselectAll">Clear All</button>
            </div>
          </div>

          @php
            $catalog = $catalog ?? \App\Services\RolePermissionService::getPermissionCatalog();
            $allPermissions = $allPermissions ?? \App\Models\Permission::all()->groupBy('module');
          @endphp

          @foreach($catalog as $moduleKey => $module)
            @php
              $modulePermissions = $allPermissions->get($moduleKey, collect());
            @endphp
            <div class="card border mb-3">
              <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 px-3">
                <div class="d-flex align-items-center">
                  <span class="fw-bold text-dark fs-14px">{{ $module['label'] }}</span>
                  <small class="text-muted ms-2 d-none d-md-inline">({{ $module['description'] }})</small>
                </div>
                <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 btn-toggle-module" data-target="{{ $moduleKey }}">
                  Toggle Module
                </button>
              </div>
              <div class="card-body p-3">
                <div class="row g-2 module-checkbox-group" id="group-{{ $moduleKey }}">
                  @foreach($modulePermissions as $perm)
                    <div class="col-md-6">
                      <div class="form-check p-2 rounded border-subtle h-100 hover-bg-light" style="background-color: #fbfbfb;">
                        <input class="form-check-input perm-checkbox me-2" type="checkbox" name="permissions[]" value="{{ $perm->id }}" id="perm_{{ $perm->id }}" {{ in_array($perm->id, old('permissions', [])) ? 'checked' : '' }}>
                        <label class="form-check-label cursor-pointer" for="perm_{{ $perm->id }}" style="cursor: pointer;">
                          <span class="fw-semibold d-block text-dark fs-13px">{{ $perm->label }}</span>
                          <code class="text-muted fs-11px">{{ $perm->name }}</code>
                        </label>
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>
            </div>
          @endforeach

          <div class="d-flex justify-content-end gap-2 pt-3 border-top">
            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary fw-bold px-4">
              <i data-lucide="save" class="icon-sm me-1"></i> Save Role
            </button>
          </div>

        </div>
      </div>
    </div>
  </div>
</form>
@endsection

@push('custom-scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const checkboxes = document.querySelectorAll('.perm-checkbox');
    const badge = document.getElementById('selectedCountBadge');
    const btnSelectAll = document.getElementById('btnSelectAll');
    const btnDeselectAll = document.getElementById('btnDeselectAll');

    function updateCount() {
      const checked = document.querySelectorAll('.perm-checkbox:checked').length;
      if (badge) badge.textContent = checked;
    }

    checkboxes.forEach(cb => cb.addEventListener('change', updateCount));
    updateCount();

    if (btnSelectAll) {
      btnSelectAll.addEventListener('click', function () {
        checkboxes.forEach(cb => cb.checked = true);
        updateCount();
      });
    }

    if (btnDeselectAll) {
      btnDeselectAll.addEventListener('click', function () {
        checkboxes.forEach(cb => cb.checked = false);
        updateCount();
      });
    }

    document.querySelectorAll('.btn-toggle-module').forEach(btn => {
      btn.addEventListener('click', function () {
        const target = this.getAttribute('data-target');
        const group = document.getElementById('group-' + target);
        if (!group) return;
        const groupCheckboxes = group.querySelectorAll('.perm-checkbox');
        const allChecked = Array.from(groupCheckboxes).every(cb => cb.checked);
        groupCheckboxes.forEach(cb => cb.checked = !allChecked);
        updateCount();
      });
    });
  });
</script>
@endpush
