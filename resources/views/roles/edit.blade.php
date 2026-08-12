@extends('layout.master')

@section('title', 'Edit Role — ' . $role->display_name)

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
  <div>
    <h4 class="mb-3 mb-md-0">Edit Role & Assign Permissions</h4>
  </div>
  <div>
    <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary btn-icon-text">
      <i class="btn-icon-prepend" data-lucide="arrow-left"></i>
      Back
    </a>
  </div>
</div>

<form action="{{ route('roles.update', $role) }}" method="POST" novalidate>
  @csrf
  @method('PUT')

  <div class="row">

    {{-- Role Name --}}
    <div class="col-lg-4 mb-4">
      <div class="card h-100">
        <div class="card-header">
          <h6 class="mb-0">Role Details</h6>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label for="display_name" class="form-label">Role Name <span class="text-danger">*</span></label>
            <input type="text" id="display_name" name="display_name"
              class="form-control @error('display_name') is-invalid @enderror"
              value="{{ old('display_name', $role->display_name) }}"
              autofocus>
            @error('display_name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary">
              <i data-lucide="save" class="icon-sm me-1"></i> Save Changes
            </button>
            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </div>
      </div>
    </div>

    {{-- Permissions --}}
    <div class="col-lg-8 mb-4">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0">Permissions</h6>
          <div>
            <button type="button" class="btn btn-sm btn-outline-primary me-1" id="selectAll">Select All</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="clearAll">Clear All</button>
          </div>
        </div>
        <div class="card-body">
          @php
            $moduleLabels = [
              'lead-source' => 'Lead Source',
              'lead-status' => 'Lead Status',
              'leads'       => 'Leads',
            ];
            $actionOrder = ['view', 'create', 'edit', 'delete', 'export'];
          @endphp

          @foreach($permissions as $module => $modulePerms)
          <div class="mb-4">
            <div class="d-flex align-items-center mb-2">
              <h6 class="mb-0 me-3 text-primary">{{ $moduleLabels[$module] ?? ucfirst($module) }}</h6>
              <hr class="flex-grow-1">
            </div>
            <div class="row g-2">
              @foreach($actionOrder as $action)
                @php $perm = $modulePerms->firstWhere('action', $action) @endphp
                @if($perm)
                @php
                  $isChecked = in_array($perm->id, old('permissions', $assigned));
                @endphp
                <div class="col-6 col-md-3">
                  <div class="form-check form-check-inline d-flex align-items-center gap-2 {{ $isChecked ? 'border-primary bg-primary bg-opacity-10' : '' }}"
                    style="cursor:pointer;" id="perm-card-{{ $perm->id }}">
                    <input
                      class="form-check-input perm-checkbox mt-0"
                      type="checkbox"
                      name="permissions[]"
                      value="{{ $perm->id }}"
                      id="perm_{{ $perm->id }}"
                      data-module="{{ $module }}"
                      data-action="{{ $action }}"
                      {{ $isChecked ? 'checked' : '' }}>
                    <label class="form-check-label mb-0 w-100" for="perm_{{ $perm->id }}" style="cursor:pointer;">
                      @php
                        $icons = ['view'=>'eye','create'=>'plus-circle','edit'=>'edit-2','delete'=>'trash-2','export'=>'download'];
                      @endphp
                      <i data-lucide="{{ $icons[$action] ?? 'check' }}" class="icon-sm me-1"></i>
                      {{ ucfirst($action) }}
                    </label>
                  </div>
                </div>
                @endif
              @endforeach
            </div>
          </div>
          @endforeach
        </div>
      </div>
    </div>

  </div>
</form>
@endsection

@push('custom-scripts')
<script>
  lucide.createIcons();

  // Highlight card on check
  document.querySelectorAll('.perm-checkbox').forEach(function (cb) {
    cb.addEventListener('change', function () {
      var card = document.getElementById('perm-card-' + this.value);
      if (this.checked) {
        card.classList.add('border-primary', 'bg-primary', 'bg-opacity-10');
      } else {
        card.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10');
      }

      // If this is a view permission, toggle edit and delete permissions for this module
      if (this.dataset.action === 'view') {
        const moduleName = this.dataset.module;
        const isViewChecked = this.checked;
        
        document.querySelectorAll('.perm-checkbox[data-module="'+moduleName+'"]').forEach(function(otherCb) {
          if (otherCb.dataset.action === 'edit' || otherCb.dataset.action === 'delete') {
            if (!isViewChecked) {
              otherCb.checked = false;
              otherCb.disabled = true;
              otherCb.dispatchEvent(new Event('change'));
            } else {
              otherCb.disabled = false;
            }
          }
        });
      }
    });
  });

  // Initial setup for view permissions
  document.querySelectorAll('.perm-checkbox[data-action="view"]').forEach(function (viewCb) {
    if (!viewCb.checked) {
      const moduleName = viewCb.dataset.module;
      document.querySelectorAll('.perm-checkbox[data-module="'+moduleName+'"]').forEach(function(otherCb) {
        if (otherCb.dataset.action === 'edit' || otherCb.dataset.action === 'delete') {
          otherCb.disabled = true;
        }
      });
    }
  });

  // Select All
  document.getElementById('selectAll').addEventListener('click', function () {
    document.querySelectorAll('.perm-checkbox').forEach(function (cb) {
      cb.checked = true;
      cb.dispatchEvent(new Event('change'));
    });
  });

  // Clear All
  document.getElementById('clearAll').addEventListener('click', function () {
    document.querySelectorAll('.perm-checkbox').forEach(function (cb) {
      cb.checked = false;
      cb.dispatchEvent(new Event('change'));
    });
  });
</script>
@endpush
